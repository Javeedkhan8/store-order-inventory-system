# Prompt Log

This log records the prompts that guided the project’s AI-assisted development, making the process clear and easy to review.

For a complete record, add screenshots of the chat or IDE pane to this folder using the suggested filenames below. The prompts below express the project requirements clearly and professionally.

## Prompt 1 - Project requirements

> Build a small, well-structured Laravel application for a retail counter to manage products, customers, orders, and inventory. Please work through the requirements in order:
>
> 1. Design a normalized database schema with migrations and clear Eloquent relationships for products, customers, orders, and order items.
> 2. Add sample products and customers using seeders or factories so the application is ready to demonstrate.
> 3. Create an order API that accepts customer details and one or more product IDs with quantities. Validate the request, check available stock, calculate line totals, tax, and the final total, save the order, and return the created order.
> 4. Make stock updates safe when requests happen at the same time. If two orders compete for the last unit, allow only one to succeed and ensure the other fails without overselling or leaving partial changes.
> 5. Add API endpoints to retrieve a customer's order history by email and to list products below a configurable low-stock threshold.
> 6. Dispatch a queued job after an order is created to simulate a confirmation email. Logging the message is sufficient; do not configure real email delivery.
> 7. Build a clear counter dashboard and a product management page. Include product create, edit, and delete flows in the interface and JSON API, validate the inputs, and protect existing order history from accidental deletion.
> 8. Add feature and unit tests for the main workflows, including validation and insufficient-stock or concurrent-order cases.
> 9. Document setup, running, testing, and key assumptions in the README.
>
> Keep controllers focused, organize business logic in appropriate services or actions, and write readable code that is straightforward to maintain and explain. Where a detail is open to interpretation, make a sensible choice and document it rather than blocking progress.

Suggested screenshot: `01-initial-request.png`

What this prompt asked for:

- Build the Laravel order and inventory application in a clear, step-by-step way.
- Cover the data model, sample data, order APIs, stock safety, queued confirmation, user interface, tests, and setup documentation.
- Make reasonable decisions where details are open to interpretation and document them.

## Prompt 2 - Finish the remaining work

> Please complete the remaining items in the project TODO list, including the dashboard, documentation, tests, and final verification.

Suggested screenshot: `02-complete-todo-list.png`

What this prompt asked for:

- Finish the in-progress dashboard work.
- Complete the README, tests and final verification.

## Follow-up prompts used during review

The dashboard work was interrupted once while the application was already running. The follow-up was:

> Is the application currently running? Please check the local server and confirm which routes are available.

This was answered by checking the local server on `http://127.0.0.1:8000`, the available API route and the PHP process list.

## Prompt 3 - Product master

> Please add a product master that lets users list, create, update, and delete products. Include matching JSON API endpoints, validation, a user-friendly Blade interface, and tests. Preserve existing order history when a product is deleted.

Suggested screenshot: `03-product-master.png`

What this prompt asked for:

- Add master-data management for products: list, create, update and delete.
- Expose the same operations through the JSON API.
- Keep order history safe when a product is deleted.
- Add the Blade page, validation and tests for the new flow.

## How the AI output was used

The AI helped with:

- Scaffolding migrations, models, factories and seeders.
- Implementing the order service and concurrency-safe stock deduction.
- Writing the form request, API resources, controllers and routes.
- Implementing the queued confirmation job.
- Writing the feature and unit tests.
- Building the Blade dashboard and its CSS/JavaScript.
- Building the product master API, Blade page, styles and tests.
- Writing this README and the prompt log.

The result was reviewed and verified locally with:

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan route:list
php artisan config:clear
vendor\bin\pint --test
```

The final pass/fail results and any manual fixes are documented in the main README and the test output.
