# Store Manager - Store Order & Inventory Mini-System

A small Laravel application for a retail counter. It records customer orders against a product catalog, calculates tax and totals, keeps stock in sync, and dispatches a queued order-confirmation job.

The project includes:

- A normalized MySQL schema and seed data.
- A JSON API for orders, order history, low-stock products and product master data.
- Blade dashboards for the counter and the product master.
- Feature and unit tests covering the happy path and edge cases.
- Concurrency-safe stock deduction.

## Requirements

- PHP 8.1 or newer
- Composer
- MySQL 5.7+ / 8+ (SQLite is used automatically by the test suite)
- Node.js is not required. The UI uses plain CSS and JavaScript with no build step.

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create the database configured in `.env` (defaults to `store_manager`):

```sql
CREATE DATABASE store_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run the migrations and sample data:

```bash
php artisan migrate --seed
```

Start the application:

```bash
php artisan serve
```

Start the queue worker in a second terminal. The confirmation job uses the database queue driver:

```bash
php artisan queue:work
```

Open `http://127.0.0.1:8000/register` to create the first user, then sign in and open `http://127.0.0.1:8000/dashboard`.

### Useful environment values

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=store_manager
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
MAIL_MAILER=log
LOW_STOCK_THRESHOLD=5
```

The queue worker is required for order-confirmation jobs to run. Without it, jobs stay in the `jobs` table until a worker processes them.

## Running the tests

```bash
php artisan test
```

The test suite uses an in-memory SQLite database, so it does not touch the development database. `phpunit.xml` sets the test database and queue driver.

## API

### Create an order

`POST /api/orders`

```json
{
  "customer_name": "Alice Johnson",
  "customer_email": "alice.johnson@example.com",
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

Successful response: `201 Created`

```json
{
  "data": {
    "id": 1,
    "order_number": "ORD-20261026-AB12CD",
    "customer": {
      "id": 1,
      "name": "Alice Johnson",
      "email": "alice.johnson@example.com"
    },
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Ethiopian Yirgacheffe Beans 1kg",
          "code": "COF-YIR-1KG",
          "price_per_unit": 1450,
          "tax_percentage": 5,
          "stock_on_hand": 38
        },
        "quantity": 2,
        "unit_price": 1450,
        "tax_percentage": 5,
        "line_subtotal": 2900,
        "line_tax": 145,
        "line_total": 3045
      }
    ],
    "subtotal": 2900,
    "tax_total": 145,
    "grand_total": 3045,
    "created_at": "2026-10-26T15:30:00+00:00"
  }
}
```

Error responses:

- `422 Unprocessable Entity` for validation errors.
- `409 Conflict` when stock is insufficient:

```json
{
  "message": "Not enough stock for Cold Brew Bottle 250ml. Requested 3, available 2.",
  "product_id": 3,
  "product_code": "BEV-COL-250",
  "requested_quantity": 3,
  "available_stock": 2
}
```

If the same `product_id` appears more than once in `items`, the quantities are merged into one order line.

### Customer order history

`GET /api/customers/orders?email=alice.johnson@example.com`

Returns `200 OK` with a `data` array of orders, newest first. An unknown email returns an empty `data` array.

### Low-stock products

`GET /api/products/low-stock`

`GET /api/products/low-stock?threshold=10`

Returns products whose `stock_on_hand` is strictly below the threshold, ordered by stock and then name. The default comes from `config/inventory.php` (`LOW_STOCK_THRESHOLD`). The response includes the active threshold:

```json
{
  "data": [],
  "meta": {
    "threshold": 5,
    "count": 0
  }
}
```

### Product master

The product master page is available at `/products`. It lists every product and supports creating, editing and deleting master records. The page is server-rendered with Blade, so it works without a JavaScript build step.

The same operations are available over JSON:

- `GET /api/products` - list products
- `POST /api/products` - create a product
- `PUT/PATCH /api/products/{product}` - update a product
- `DELETE /api/products/{product}` - delete a product

Create and update payload:

```json
{
  "name": "Ethiopian Yirgacheffe Beans 1kg",
  "code": "COF-YIR-1KG",
  "price_per_unit": 1450,
  "tax_percentage": 5,
  "stock_on_hand": 40
}
```

Validation rules:

- Name is required.
- Code is required, unique, trimmed and stored in uppercase. Updating a product keeps its own code valid.
- Price must be zero or greater.
- Tax must be between 0 and 100.
- Stock must be zero or greater.

A successful delete returns `204 No Content`. If the product already appears in an order, deletion returns `409 Conflict` so order history is preserved. Edit the product instead.

## How order creation works

The HTTP controller is intentionally thin. Validation lives in `StoreOrderRequest`, and the business logic lives in `OrderService`.

1. The request is validated with a form request.
2. The product lines are merged and sorted by product ID.
3. A database transaction starts.
4. Products are loaded with `lockForUpdate()` so concurrent orders for the same product queue behind each other.
5. Stock availability is checked and each line's subtotal, tax and total are calculated.
6. The customer is reused by email, or created if new.
7. The order and its line items are created.
8. Stock is deducted using a conditional `UPDATE ... WHERE stock_on_hand >= quantity`.
9. The transaction commits.
10. The `SendOrderConfirmationEmail` job is dispatched.

## Concurrency and no-overselling

The brief calls out the case where two orders arrive at the same time and only one unit is left.

The implementation uses three layers of protection:

- **Row locking:** products are selected with `SELECT ... FOR UPDATE` inside a transaction. In MySQL/InnoDB, the second transaction waits until the first commits, then reads the updated stock.
- **Conditional stock update:** stock is deducted with `UPDATE products SET stock_on_hand = stock_on_hand - ? WHERE id = ? AND stock_on_hand >= ?`. If the update affects zero rows, the transaction is rolled back with an `InsufficientStockException`. This also protects databases where row locking is not available, such as SQLite used by the test suite.
- **Consistent lock order:** product IDs are sorted before locking, reducing the chance of deadlocks when two orders contain the same products in different orders. `DB::transaction(..., 3)` retries deadlocks up to three times.

The result is that the first valid order succeeds, and a competing order fails cleanly with `409 Conflict` and no partial order or stock change.

## Queue and simulated confirmation email

`App\Jobs\SendOrderConfirmationEmail` implements `ShouldQueue`. It is dispatched after the order transaction commits.

The job simulates the confirmation email with a structured log entry containing the recipient, order number, purchased items, quantities, line totals, subtotal, tax and grand total. It does not use SMTP or send external email, as allowed by the assignment.

Run the migrations so the `jobs` table exists, then keep a worker running in a separate terminal:

```bash
php artisan migrate
php artisan queue:work
```

After Generate bill is clicked, the order is saved and its confirmation job is queued. The worker writes the simulated email to `storage/logs/laravel.log`, keeping job processing separate from the order request. Inspect failed jobs with `php artisan queue:failed`.

## Tests

The suite currently covers:

- Order creation, stock deduction and job dispatch.
- Tax and total calculation across multiple products, including per-line rounding.
- Reusing an existing customer by email.
- Merging duplicate product lines.
- Insufficient stock returning `409` without creating an order or changing stock.
- Only one of two attempts succeeding for the last unit in stock.
- Request validation for the order and each product line.
- Customer order history filtering, ordering and unknown emails.
- Low-stock filtering with the configured default and a request override.
- Product master listing, creation, update and deletion through both the API and the web form.
- Product validation, duplicate code handling and the delete guard for products with order history.
- The confirmation job logging a simulated email to the customer.
- The dashboard rendering products.

Run:

```bash
php artisan test
```

## Assumptions and design decisions

1. **The demo uses Indian Rupees (INR).** Amounts are stored as decimals without a currency column, and the UI displays them with the `₹` symbol. The backend itself is currency-agnostic.
2. **Customers are identified by email.** If an order uses an existing email, the existing customer is reused and their stored name is not overwritten. This avoids silently renaming a customer because of a typo in a new order.
3. **Duplicate product lines are merged.** Sending the same product twice adds the quantities together. The database enforces one row per product per order.
4. **Tax is calculated per line and rounded to two decimals per line.** Line totals are then summed for the order totals. This keeps the stored line values and the order totals consistent.
5. **"Below the threshold" is interpreted strictly.** A product with stock equal to the threshold is not low stock. The threshold defaults to `5` and can be overridden with `?threshold=`.
6. **Insufficient stock is a domain conflict, not a validation error.** It returns `409 Conflict` with details about the product and available stock. Invalid input still returns `422`.
7. **Unknown customer email returns an empty order list rather than a 404.** This keeps the lookup endpoint simple for a counter UI.
8. **Order items snapshot the unit price and tax percentage.** Changing a product's price or tax later does not rewrite historical orders.
9. **Operational web pages require login.** The JSON API routes are not protected by authentication in this assignment project. API rate limiting comes from Laravel's default `api` middleware.
10. **No pagination is included for order history.** The dataset is small for the assignment. Pagination would be the next addition for a real store.
11. **No idempotency key is implemented.** A double-click can create two orders if stock allows. A production version would accept an idempotency key and enforce it with a unique index.
12. **Stock can be adjusted from the product master.** There is no order cancellation or automatic restocking workflow; a manual stock correction is an explicit master-data edit.
13. **Products with order history cannot be deleted.** Order items keep a snapshot of the product and the foreign key restricts deletion. The controller returns a clear conflict message instead of failing at the database level.

## Project map

| Area | Location |
| --- | --- |
| Migrations | `database/migrations` |
| Models | `app/Models` |
| Order business logic | `app/Services/OrderService.php` |
| Order validation | `app/Http/Requests/StoreOrderRequest.php` |
| Product validation | `app/Http/Requests/ProductRequest.php` |
| Product API controller | `app/Http/Controllers/Api/ProductController.php` |
| Product master page controller | `app/Http/Controllers/ProductMasterController.php` |
| Stock conflict response | `app/Exceptions/InsufficientStockException.php` |
| Queued job | `app/Jobs/SendOrderConfirmationEmail.php` |
| API controllers | `app/Http/Controllers/Api` |
| API routes | `routes/api.php` |
| Dashboard controller | `app/Http/Controllers/DashboardController.php` |
| Dashboard view and assets | `resources/views`, `public/css`, `public/js` |
| Product master view | `resources/views/products/index.blade.php` |
| Seeders and factories | `database/seeders`, `database/factories` |
| Tests | `tests/Feature`, `tests/Unit` |

## AI-assisted development

See [prompts/prompt-log.md](prompts/prompt-log.md) for the prompt log. If screenshots are required for submission, add them to the `prompts/` folder alongside the log.
