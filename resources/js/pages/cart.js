import { apiFetch, ApiError, requireAuth } from '../api';
import { formatCurrency, escapeHtml, errorState, placeholderImage, showToast } from '../ui';

function cartItemRow(item) {
    const product = item.product;
    const image = (product.images || []).find((i) => i.is_primary) || (product.images || [])[0];
    const subtotal = item.quantity * Number(product.price);

    return `
        <div class="surface flex items-center gap-4 p-4" data-item-id="${item.id}">
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                ${
                    image
                        ? `<img src="/storage/${image.image}" alt="${escapeHtml(product.name)}" class="h-full w-full object-cover">`
                        : placeholderImage('h-8 w-8')
                }
            </div>
            <div class="min-w-0 flex-1">
                <a href="/productos/${product.id}" class="line-clamp-1 text-sm font-medium text-slate-900 hover:text-brand-700">${escapeHtml(product.name)}</a>
                <p class="mt-1 text-sm text-slate-500">${formatCurrency(product.price)} c/u</p>
                <div class="mt-2 flex items-center gap-3">
                    <div class="flex items-center rounded-lg ring-1 ring-slate-300">
                        <button type="button" data-decrement class="px-2.5 py-1 text-slate-600 hover:bg-slate-50" aria-label="Restar">−</button>
                        <input type="number" min="1" max="${product.stock}" value="${item.quantity}" data-quantity-input class="w-12 border-0 bg-transparent text-center text-sm focus:ring-0">
                        <button type="button" data-increment class="px-2.5 py-1 text-slate-600 hover:bg-slate-50" aria-label="Sumar">+</button>
                    </div>
                    <button type="button" data-remove class="text-sm font-medium text-red-600 hover:text-red-700">Eliminar</button>
                </div>
                <p class="field-error hidden" data-item-error></p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-slate-900">${formatCurrency(subtotal)}</p>
        </div>
    `;
}

function emptyCart() {
    return `
        <div class="surface flex flex-col items-center justify-center px-6 py-16 text-center lg:col-span-3">
            <svg class="mb-3 h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.694 2.591-7.171.055-.227-.1-.443-.34-.443H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            <p class="font-medium text-slate-900">Tu carrito está vacío</p>
            <a href="/catalogo" class="btn-primary mt-4">Ir al catálogo</a>
        </div>
    `;
}

async function loadCart() {
    const container = document.getElementById('cart-content');
    if (!container) return;

    try {
        const data = await apiFetch('/cart');
        const items = data.cart?.items || [];

        if (!items.length) {
            container.innerHTML = emptyCart();
            return;
        }

        container.innerHTML = `
            <div class="space-y-3 lg:col-span-2">${items.map(cartItemRow).join('')}</div>
            <div class="surface flex flex-col gap-4 p-5">
                <div class="flex items-center justify-between text-base font-semibold text-slate-900">
                    <span>Total</span>
                    <span id="cart-total">${formatCurrency(data.total)}</span>
                </div>
                <a href="/checkout" class="btn-primary btn-block">Continuar al checkout</a>
                <button type="button" id="clear-cart" class="btn-ghost btn-block">Vaciar carrito</button>
            </div>
        `;

        wireItemActions();
        document.getElementById('clear-cart')?.addEventListener('click', clearCart);
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo cargar el carrito.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadCart);
    }
}

function wireItemActions() {
    document.querySelectorAll('[data-item-id]').forEach((row) => {
        const itemId = row.dataset.itemId;
        const input = row.querySelector('[data-quantity-input]');

        row.querySelector('[data-increment]')?.addEventListener('click', () => {
            input.value = Number(input.value) + 1;
            updateQuantity(itemId, Number(input.value), row);
        });
        row.querySelector('[data-decrement]')?.addEventListener('click', () => {
            const next = Math.max(1, Number(input.value) - 1);
            input.value = next;
            updateQuantity(itemId, next, row);
        });
        input.addEventListener('change', () => {
            const next = Math.max(1, Number(input.value) || 1);
            input.value = next;
            updateQuantity(itemId, next, row);
        });
        row.querySelector('[data-remove]')?.addEventListener('click', () => removeItem(itemId));
    });
}

async function updateQuantity(itemId, quantity, row) {
    const errorEl = row.querySelector('[data-item-error]');
    errorEl.classList.add('hidden');

    try {
        await apiFetch(`/cart/items/${itemId}`, {
            method: 'PATCH',
            body: JSON.stringify({ quantity }),
        });
        loadCart();
        window.refreshCartBadge?.();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo actualizar la cantidad.';
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }
}

async function removeItem(itemId) {
    try {
        await apiFetch(`/cart/items/${itemId}`, { method: 'DELETE' });
        showToast('Producto eliminado del carrito.', 'success');
        loadCart();
        window.refreshCartBadge?.();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo eliminar el producto.';
        showToast(message, 'error');
    }
}

async function clearCart() {
    try {
        await apiFetch('/cart', { method: 'DELETE' });
        loadCart();
        window.refreshCartBadge?.();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo vaciar el carrito.';
        showToast(message, 'error');
    }
}

export function init() {
    if (!requireAuth()) return;
    loadCart();
}
