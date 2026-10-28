(function () {
    'use strict';

    var config = window.STORE || { products: [], lowStockThreshold: 5 };

    var state = {
        products: config.products.map(function (product) {
            return Object.assign({}, product);
        }),
        cart: [],
        lowStockThreshold: Number(config.lowStockThreshold) || 0
    };

    var elements = {
        productGrid: document.getElementById('productGrid'),
        thresholdInput: document.getElementById('thresholdInput'),
        lowStockOnly: document.getElementById('lowStockOnly'),
        productCount: document.getElementById('productCount'),
        lowStockCount: document.getElementById('lowStockCount'),
        totalUnits: document.getElementById('totalUnits'),
        cartItems: document.getElementById('cartItems'),
        clearCart: document.getElementById('clearCart'),
        subtotal: document.getElementById('subtotal'),
        taxTotal: document.getElementById('taxTotal'),
        grandTotal: document.getElementById('grandTotal'),
        orderForm: document.getElementById('orderForm'),
        customerName: document.getElementById('customerName'),
        customerEmail: document.getElementById('customerEmail'),
        submitOrder: document.getElementById('submitOrder'),
        orderMessage: document.getElementById('orderMessage'),
        historyForm: document.getElementById('historyForm'),
        historyEmail: document.getElementById('historyEmail'),
        historyResults: document.getElementById('historyResults')
    };

    function money(value) {
        return '₹' + Number(value).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function round(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    function productById(id) {
        var numericId = Number(id);

        return state.products.find(function (product) {
            return product.id === numericId;
        });
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function showMessage(message, type) {
        elements.orderMessage.textContent = message;
        elements.orderMessage.className = 'message message--' + type;
        elements.orderMessage.hidden = false;
    }

    function hideMessage() {
        elements.orderMessage.hidden = true;
    }

    function addToCart(productId) {
        var product = productById(productId);

        if (!product) {
            return;
        }

        var line = state.cart.find(function (item) {
            return item.productId === product.id;
        });

        var currentQuantity = line ? line.quantity : 0;

        if (product.stock <= currentQuantity) {
            showMessage('Only ' + product.stock + ' unit(s) of ' + product.name + ' are available.', 'error');
            return;
        }

        if (line) {
            line.quantity += 1;
        } else {
            state.cart.push({ productId: product.id, quantity: 1 });
        }

        hideMessage();
        renderCart();
    }

    function changeQuantity(productId, quantity) {
        var line = state.cart.find(function (item) {
            return item.productId === Number(productId);
        });

        var product = productById(productId);

        if (!line || !product) {
            return;
        }

        line.quantity = Math.max(1, Math.min(Number(quantity) || 0, product.stock));
        renderCart();
    }

    function removeFromCart(productId) {
        state.cart = state.cart.filter(function (item) {
            return item.productId !== Number(productId);
        });

        renderCart();
    }

    function calculateTotals() {
        return state.cart.reduce(function (totals, line) {
            var product = productById(line.productId);

            if (!product) {
                return totals;
            }

            var lineSubtotal = round(product.price * line.quantity);
            var lineTax = round(lineSubtotal * product.tax / 100);

            totals.subtotal = round(totals.subtotal + lineSubtotal);
            totals.tax = round(totals.tax + lineTax);
            totals.grand = round(totals.grand + lineSubtotal + lineTax);

            return totals;
        }, { subtotal: 0, tax: 0, grand: 0 });
    }

    function renderCart() {
        if (state.cart.length === 0) {
            elements.cartItems.innerHTML = '<p class="empty-state empty-state--small">Add a product to begin.</p>';
        } else {
            elements.cartItems.innerHTML = state.cart.map(function (line) {
                var product = productById(line.productId);

                if (!product) {
                    return '';
                }

                return ''
                    + '<div class="cart-line" data-cart-line="' + product.id + '">'
                    + '    <div>'
                    + '        <span class="cart-line__name">' + escapeHtml(product.name) + '</span>'
                    + '        <span class="cart-line__meta">' + money(product.price) + ' each &middot; ' + product.tax + '% tax</span>'
                    + '    </div>'
                    + '    <input type="number" min="1" max="' + product.stock + '" value="' + line.quantity + '" data-cart-quantity="' + product.id + '" aria-label="Quantity for ' + escapeHtml(product.name) + '">'
                    + '    <button type="button" class="icon-button" data-remove-product="' + product.id + '" aria-label="Remove ' + escapeHtml(product.name) + '">&times;</button>'
                    + '</div>';
            }).join('');
        }

        var totals = calculateTotals();

        elements.subtotal.textContent = money(totals.subtotal);
        elements.taxTotal.textContent = money(totals.tax);
        elements.grandTotal.textContent = money(totals.grand);
        elements.submitOrder.disabled = state.cart.length === 0;
    }

    function refreshCatalog() {
        if (!elements.productGrid) {
            return;
        }

        var lowStockCount = 0;
        var totalUnits = 0;

        elements.productGrid.querySelectorAll('[data-product-id]').forEach(function (card) {
            var product = productById(card.dataset.productId);

            if (!product) {
                return;
            }

            var isLow = product.stock < state.lowStockThreshold;

            if (isLow) {
                lowStockCount += 1;
            }

            totalUnits += product.stock;

            card.classList.toggle('is-low', isLow);
            card.classList.toggle('is-hidden', elements.lowStockOnly.checked && !isLow);

            var badge = card.querySelector('[data-stock-badge]');

            if (badge) {
                badge.textContent = product.stock + ' in stock';
                badge.classList.toggle('stock-badge--low', isLow);
            }

            var addButton = card.querySelector('[data-add-product]');

            if (addButton) {
                addButton.disabled = product.stock === 0;
                addButton.textContent = product.stock === 0 ? 'Out of stock' : 'Add to order';
            }
        });

        elements.productCount.textContent = String(state.products.length);
        elements.lowStockCount.textContent = String(lowStockCount);
        elements.totalUnits.textContent = String(totalUnits);
    }

    function applyCompletedOrder(order) {
        if (!order || !Array.isArray(order.items)) {
            return;
        }

        order.items.forEach(function (item) {
            var product = item.product ? productById(item.product.id) : null;

            if (product) {
                product.stock = Math.max(0, product.stock - Number(item.quantity));
            }
        });
    }

    function firstValidationError(payload) {
        if (!payload.errors) {
            return payload.message || 'The submitted data is invalid.';
        }

        var firstKey = Object.keys(payload.errors)[0];

        return payload.errors[firstKey][0];
    }

    function submitOrder(event) {
        event.preventDefault();
        hideMessage();

        var customerName = elements.customerName.value.trim();
        var customerEmail = elements.customerEmail.value.trim();

        if (!customerName || !customerEmail) {
            showMessage('Customer name and email are required.', 'error');
            return;
        }

        if (state.cart.length === 0) {
            showMessage('Add at least one product before placing the order.', 'error');
            return;
        }

        elements.submitOrder.disabled = true;
        elements.submitOrder.textContent = 'Placing order...';

        fetch('/api/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                customer_name: customerName,
                customer_email: customerEmail,
                items: state.cart.map(function (line) {
                    return { product_id: line.productId, quantity: line.quantity };
                })
            })
        })
            .then(function (response) {
                return response.json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (payload) {
                        if (!response.ok) {
                            var message = response.status === 422
                                ? firstValidationError(payload)
                                : payload.message || 'The order could not be placed.';

                            throw new Error(message);
                        }

                        return payload.data;
                    });
            })
            .then(function (order) {
                applyCompletedOrder(order);
                state.cart = [];
                renderCart();
                refreshCatalog();

                var name = elements.customerName.value.trim();

                elements.customerName.value = '';
                elements.customerEmail.value = '';
                elements.historyEmail.value = order.customer ? order.customer.email : '';

                showMessage('Order ' + order.order_number + ' placed successfully for ' + name + '. Total: ' + money(order.grand_total) + '.', 'success');
            })
            .catch(function (error) {
                showMessage(error.message, 'error');
            })
            .finally(function () {
                elements.submitOrder.disabled = state.cart.length === 0;
                elements.submitOrder.textContent = 'Place order';
            });
    }

    function renderHistory(orders) {
        if (!orders.length) {
            elements.historyResults.innerHTML = '<p class="empty-state empty-state--small">No orders found for that email.</p>';
            return;
        }

        elements.historyResults.innerHTML = orders.map(function (order) {
            var items = (order.items || []).map(function (item) {
                var name = item.product ? item.product.name : 'Product';

                return ''
                    + '<div class="order-card__item">'
                    + '    <span>' + escapeHtml(name) + ' &times; ' + item.quantity + '</span>'
                    + '    <span>' + money(item.line_total) + '</span>'
                    + '</div>';
            }).join('');

            return ''
                + '<article class="order-card">'
                + '    <div class="order-card__header">'
                + '        <div>'
                + '            <div class="order-card__number">' + escapeHtml(order.order_number) + '</div>'
                + '            <div class="order-card__date">' + new Date(order.created_at).toLocaleString() + '</div>'
                + '        </div>'
                + '        <div class="order-card__total">' + money(order.grand_total) + '</div>'
                + '    </div>'
                + '    <div class="order-card__items">' + items + '</div>'
                + '</article>';
        }).join('');
    }

    function loadHistory(event) {
        event.preventDefault();

        var email = elements.historyEmail.value.trim();

        if (!email) {
            elements.historyResults.innerHTML = '<p class="empty-state empty-state--small">Enter a customer email first.</p>';
            return;
        }

        elements.historyResults.innerHTML = '<p class="empty-state empty-state--small">Loading orders...</p>';

        fetch('/api/customers/orders?email=' + encodeURIComponent(email), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) {
                return response.json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to load order history.');
                        }

                        return payload.data || [];
                    });
            })
            .then(renderHistory)
            .catch(function (error) {
                elements.historyResults.innerHTML = '<p class="message message--error">' + escapeHtml(error.message) + '</p>';
            });
    }

    function init() {
        if (elements.productGrid) {
            elements.productGrid.addEventListener('click', function (event) {
                var button = event.target.closest('[data-add-product]');

                if (button) {
                    addToCart(button.dataset.addProduct);
                }
            });
        }

        if (elements.cartItems) {
            elements.cartItems.addEventListener('click', function (event) {
                var button = event.target.closest('[data-remove-product]');

                if (button) {
                    removeFromCart(button.dataset.removeProduct);
                }
            });

            elements.cartItems.addEventListener('change', function (event) {
                var input = event.target.closest('[data-cart-quantity]');

                if (input) {
                    changeQuantity(input.dataset.cartQuantity, input.value);
                }
            });
        }

        if (elements.orderForm) {
            elements.orderForm.addEventListener('submit', submitOrder);
        }

        if (elements.historyForm) {
            elements.historyForm.addEventListener('submit', loadHistory);
        }

        if (elements.clearCart) {
            elements.clearCart.addEventListener('click', function () {
                state.cart = [];
                hideMessage();
                renderCart();
            });
        }

        if (elements.thresholdInput) {
            elements.thresholdInput.addEventListener('input', function () {
                state.lowStockThreshold = Number(elements.thresholdInput.value) || 0;
                refreshCatalog();
            });
        }

        if (elements.lowStockOnly) {
            elements.lowStockOnly.addEventListener('change', refreshCatalog);
        }

        renderCart();
        refreshCatalog();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
