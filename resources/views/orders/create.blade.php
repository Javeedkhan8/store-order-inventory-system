@extends('layouts.app')

@section('title', 'Create Order | Store Manager')

@section('content')
    <div class="pos-layout">
        <section class="panel pos-catalog-panel" aria-labelledby="catalogTitle">
            <header class="pos-section-header">
                <div>
                    <p class="eyebrow">Counter sales</p>
                    <h1 id="catalogTitle">Create order</h1>
                </div>
                <span class="pos-product-count" id="productCount">{{ $products->count() }} products</span>
            </header>

            <div class="pos-catalog-tools">
                <label class="pos-search">
                    <span class="sr-only">Search products</span>
                    <input id="productSearch" type="search" placeholder="Search product or scan code" autocomplete="off">
                </label>
                <div class="pos-category-list" role="group" aria-label="Product categories">
                    <button class="pos-category is-active" type="button" data-category="all" aria-pressed="true">All</button>
                    <button class="pos-category" type="button" data-category="grocery" aria-pressed="false">Grocery</button>
                    <button class="pos-category" type="button" data-category="care" aria-pressed="false">Care</button>
                </div>
            </div>

            @if (session('error'))
                <div class="flash flash--error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash--error">Please check the customer details and order items.</div>
            @endif

            <div class="pos-product-grid" id="productGrid" aria-live="polite"></div>
            <p class="pos-empty-state" id="productEmpty" hidden>No matching products.</p>
        </section>

        <aside class="panel pos-checkout-panel" aria-labelledby="orderTitle">
            <form method="POST" action="{{ route('orders.store') }}" id="webOrderForm">
                @csrf
                <header class="pos-order-header">
                    <div>
                        <h2 id="orderTitle">Current order</h2>
                        <p>Customer and payment</p>
                    </div>
                </header>

                <div class="pos-customer-fields">
                    <label class="field">
                        <span>Customer name</span>
                        <input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required>
                    </label>
                    <label class="field">
                        <span>Customer email</span>
                        <input name="customer_email" type="email" value="{{ old('customer_email') }}" autocomplete="email" required>
                    </label>
                    <p class="pos-customer-match" id="customerMatchStatus" aria-live="polite">Enter a saved customer email to fill their name.</p>
                </div>

                <div class="pos-order-lines" id="orderLines" aria-live="polite">
                    <p class="pos-empty-state">Add products to begin this order.</p>
                </div>

                <div class="pos-checkout-bottom">
                    <dl class="pos-totals">
                        <div><dt>Subtotal</dt><dd id="orderSubtotal">₹0.00</dd></div>
                        <div><dt>Tax</dt><dd id="orderTax">₹0.00</dd></div>
                        <div class="pos-total"><dt>Total</dt><dd id="orderTotal">₹0.00</dd></div>
                    </dl>

                    <div class="pos-tender" aria-label="Cash received">
                        <span class="pos-tender-label">Cash received</span>
                        <div class="pos-tender-options">
                            <button type="button" class="pos-tender-button" data-cash-amount="50">₹50</button>
                            <button type="button" class="pos-tender-button" data-cash-amount="100">₹100</button>
                            <button type="button" class="pos-tender-button" data-cash-amount="200">₹200</button>
                            <button type="button" class="pos-tender-button is-selected" id="exactCash">Exact</button>
                        </div>
                        <div class="pos-change-row" aria-live="polite">
                            <span id="cashMessage">Exact amount</span>
                            <strong id="cashChange">₹0.00</strong>
                        </div>
                    </div>

                    <button type="submit" class="button button--primary pos-submit" id="submitOrder" disabled>Generate bill</button>
                </div>
            </form>
        </aside>
    </div>

    <script>
        const products = @json($products->values());
        const customers = @json($customers);
        const lowStockThreshold = @json($lowStockThreshold);
        const productGrid = document.getElementById('productGrid');
        const productSearch = document.getElementById('productSearch');
        const productEmpty = document.getElementById('productEmpty');
        const orderLines = document.getElementById('orderLines');
        const orderForm = document.getElementById('webOrderForm');
        const customerNameInput = orderForm.elements.customer_name;
        const customerEmailInput = orderForm.elements.customer_email;
        const customerMatchStatus = document.getElementById('customerMatchStatus');
        const submitOrder = document.getElementById('submitOrder');
        const quantities = new Map();
        let activeCategory = 'all';
        let cashReceived = 0;
        let exactCashSelected = true;
        let matchedCustomerName = null;
        let isSubmitting = false;

        const money = (amount) => new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: 2,
        }).format(amount);

        function productCategory(product) {
            return /toothpaste|toothbrush|soap|shampoo|conditioner|lotion|cream|sanitizer|detergent/i.test(`${product.name} ${product.code}`)
                ? 'care'
                : 'grocery';
        }

        function addProduct(product) {
            const currentQuantity = quantities.get(product.id) || 0;
            if (currentQuantity >= Number(product.stock_on_hand)) {
                return;
            }
            quantities.set(product.id, currentQuantity + 1);
            renderProducts();
            renderOrder();
        }

        function matchExistingCustomer() {
            const email = customerEmailInput.value.trim().toLowerCase();
            const customer = customers.find((entry) => entry.email.toLowerCase() === email);

            if (customer) {
                customerNameInput.value = customer.name;
                matchedCustomerName = customer.name;
                customerMatchStatus.textContent = `Saved customer found: ${customer.name}`;
                customerMatchStatus.classList.add('is-found');
                return;
            }

            if (matchedCustomerName && customerNameInput.value === matchedCustomerName) {
                customerNameInput.value = '';
            }
            matchedCustomerName = null;
            customerMatchStatus.textContent = email
                ? 'New customer email'
                : 'Enter a saved customer email to fill their name.';
            customerMatchStatus.classList.remove('is-found');
        }

        function renderProducts() {
            const query = productSearch.value.trim().toLowerCase();
            const filteredProducts = products.filter((product) => {
                const matchesQuery = `${product.name} ${product.code}`.toLowerCase().includes(query);
                return matchesQuery && (activeCategory === 'all' || productCategory(product) === activeCategory);
            });

            productGrid.replaceChildren();
            productEmpty.hidden = filteredProducts.length > 0;

            filteredProducts.forEach((product) => {
                const card = document.createElement('button');
                card.type = 'button';
                const quantityInOrder = quantities.get(product.id) || 0;
                const stockLimitReached = quantityInOrder >= Number(product.stock_on_hand);
                card.className = quantityInOrder > 0
                    ? 'pos-product-card is-added'
                    : 'pos-product-card';
                card.disabled = Number(product.stock_on_hand) < 1 || stockLimitReached;
                card.setAttribute('aria-label', quantityInOrder > 0
                    ? `${product.name}, ${quantityInOrder} in current order`
                    : `Add ${product.name} to current order`);

                const name = document.createElement('strong');
                name.className = 'pos-product-name';
                name.textContent = product.name;

                const price = document.createElement('span');
                price.className = 'pos-product-price';
                price.textContent = money(Number(product.price_per_unit));

                const stock = document.createElement('span');
                stock.className = Number(product.stock_on_hand) <= lowStockThreshold
                    ? 'pos-product-stock is-low'
                    : 'pos-product-stock';
                stock.textContent = Number(product.stock_on_hand) < 1
                    ? 'Out of stock'
                    : `${product.stock_on_hand} in stock`;

                const action = document.createElement('span');
                action.className = 'pos-product-action';
                if (Number(product.stock_on_hand) < 1) {
                    action.textContent = 'Unavailable';
                    action.classList.add('is-unavailable');
                } else if (quantityInOrder > 0) {
                    action.textContent = stockLimitReached
                        ? `Added to order · ${quantityInOrder}`
                        : `Added to order · ${quantityInOrder} · Add another`;
                    action.classList.add('is-added');
                } else {
                    action.textContent = '+ Add to order';
                }

                card.append(name, price, stock, action);
                card.addEventListener('click', () => addProduct(product));
                productGrid.appendChild(card);
            });
        }

        function renderOrder() {
            const selectedProducts = products.filter((product) => quantities.has(product.id));
            orderLines.replaceChildren();
            let subtotal = 0;
            let tax = 0;
            let itemIndex = 0;

            selectedProducts.forEach((product) => {
                const quantity = quantities.get(product.id);
                const lineSubtotal = Math.round(Number(product.price_per_unit) * quantity * 100) / 100;
                const lineTax = Math.round(lineSubtotal * Number(product.tax_percentage) / 100 * 100) / 100;
                subtotal += lineSubtotal;
                tax += lineTax;

                const row = document.createElement('div');
                row.className = 'pos-order-line';

                const hiddenProduct = document.createElement('input');
                hiddenProduct.type = 'hidden';
                hiddenProduct.name = `items[${itemIndex}][product_id]`;
                hiddenProduct.value = product.id;

                const hiddenQuantity = document.createElement('input');
                hiddenQuantity.type = 'hidden';
                hiddenQuantity.name = `items[${itemIndex}][quantity]`;
                hiddenQuantity.value = quantity;

                const item = document.createElement('div');
                item.className = 'pos-order-line__item';
                const itemName = document.createElement('strong');
                itemName.textContent = product.name;
                const itemMeta = document.createElement('span');
                itemMeta.textContent = `${quantity} × ${money(Number(product.price_per_unit))}`;
                item.append(itemName, itemMeta);

                const lineTotal = document.createElement('strong');
                lineTotal.className = 'pos-order-line__total';
                lineTotal.textContent = money(lineSubtotal + lineTax);

                const controls = document.createElement('div');
                controls.className = 'pos-quantity-controls';
                const decrease = document.createElement('button');
                decrease.type = 'button';
                decrease.className = 'pos-quantity-button';
                decrease.textContent = '−';
                decrease.setAttribute('aria-label', `Decrease ${product.name} quantity`);
                decrease.addEventListener('click', () => changeQuantity(product, -1));
                const quantityLabel = document.createElement('span');
                quantityLabel.textContent = quantity;
                const increase = document.createElement('button');
                increase.type = 'button';
                increase.className = 'pos-quantity-button';
                increase.textContent = '+';
                increase.setAttribute('aria-label', `Increase ${product.name} quantity`);
                increase.disabled = quantity >= Number(product.stock_on_hand);
                increase.addEventListener('click', () => changeQuantity(product, 1));
                controls.append(decrease, quantityLabel, increase);

                row.append(hiddenProduct, hiddenQuantity, item, lineTotal, controls);
                orderLines.appendChild(row);
                itemIndex++;
            });

            if (selectedProducts.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'pos-empty-state';
                empty.textContent = 'Add products to begin this order.';
                orderLines.appendChild(empty);
            }

            subtotal = Math.round(subtotal * 100) / 100;
            tax = Math.round(tax * 100) / 100;
            const total = subtotal + tax;
            document.getElementById('orderSubtotal').textContent = money(subtotal);
            document.getElementById('orderTax').textContent = money(tax);
            document.getElementById('orderTotal').textContent = money(total);

            if (exactCashSelected) {
                cashReceived = total;
            }
            const balance = cashReceived - total;
            document.getElementById('cashMessage').textContent = balance >= 0 ? 'Change due' : 'Amount remaining';
            document.getElementById('cashChange').textContent = money(Math.abs(balance));
            submitOrder.disabled = selectedProducts.length === 0;
        }

        function changeQuantity(product, change) {
            const quantity = (quantities.get(product.id) || 0) + change;
            if (quantity <= 0) {
                quantities.delete(product.id);
            } else if (quantity <= Number(product.stock_on_hand)) {
                quantities.set(product.id, quantity);
            }
            renderProducts();
            renderOrder();
        }

        productSearch.addEventListener('input', renderProducts);
        customerEmailInput.addEventListener('input', matchExistingCustomer);
        document.querySelectorAll('.pos-category').forEach((button) => {
            button.addEventListener('click', () => {
                activeCategory = button.dataset.category;
                document.querySelectorAll('.pos-category').forEach((categoryButton) => {
                    const isActive = categoryButton === button;
                    categoryButton.classList.toggle('is-active', isActive);
                    categoryButton.setAttribute('aria-pressed', String(isActive));
                });
                renderProducts();
            });
        });

        document.querySelectorAll('[data-cash-amount]').forEach((button) => {
            button.addEventListener('click', () => {
                exactCashSelected = false;
                cashReceived = Number(button.dataset.cashAmount);
                document.querySelectorAll('.pos-tender-button').forEach((cashButton) => {
                    cashButton.classList.toggle('is-selected', cashButton === button);
                });
                renderOrder();
            });
        });

        document.getElementById('exactCash').addEventListener('click', (event) => {
            exactCashSelected = true;
            document.querySelectorAll('.pos-tender-button').forEach((cashButton) => {
                cashButton.classList.toggle('is-selected', cashButton === event.currentTarget);
            });
            renderOrder();
        });

        orderForm.addEventListener('submit', (event) => {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            isSubmitting = true;
            orderForm.setAttribute('aria-busy', 'true');
            submitOrder.disabled = true;
            submitOrder.innerHTML = '<span class="pos-spinner" aria-hidden="true"></span><span>Generating bill...</span>';
        });

        renderProducts();
        renderOrder();
    </script>
@endsection