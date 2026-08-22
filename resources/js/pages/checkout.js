import { apiFetch, ApiError, requireAuth } from '../api';
import { formatCurrency, escapeHtml, errorState, clearFormErrors, showFormErrors, showToast } from '../ui';

const FIELDS = [
    { name: 'recipient_name', label: 'Nombre del receptor', required: true },
    { name: 'phone', label: 'Teléfono', required: true },
    { name: 'address', label: 'Dirección', required: true, wide: true },
    { name: 'number', label: 'Número', required: false },
    { name: 'city', label: 'Ciudad / localidad', required: true },
    { name: 'state', label: 'Provincia', required: true },
    { name: 'postal_code', label: 'Código postal', required: true },
];

function formHtml() {
    return `
        <form id="checkout-form" class="surface space-y-5 p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold text-slate-900">Datos de entrega</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                ${FIELDS.map(
                    (field) => `
                    <div class="${field.wide ? 'sm:col-span-2' : ''}">
                        <label for="field-${field.name}" class="field-label">${field.label}${field.required ? '' : ' (opcional)'}</label>
                        <input type="text" id="field-${field.name}" name="${field.name}" class="field-input" ${field.required ? 'required' : ''}>
                        <p class="field-error hidden" data-error-for="${field.name}"></p>
                    </div>
                `
                ).join('')}
                <div class="sm:col-span-2">
                    <label for="field-notes" class="field-label">Observaciones (opcional)</label>
                    <textarea id="field-notes" name="notes" rows="3" class="field-input"></textarea>
                    <p class="field-error hidden" data-error-for="notes"></p>
                </div>
            </div>
            <p id="checkout-form-error" class="field-error hidden"></p>
            <button type="submit" class="btn-primary btn-block">
                Continuar al pago
            </button>
            <p class="text-center text-xs text-slate-400">Vas a ser redirigido a Mercado Pago para completar el pago de forma segura.</p>
        </form>
    `;
}

function summaryHtml(cart) {
    const items = cart.cart?.items || [];
    return `
        <div class="surface h-fit space-y-4 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Resumen</h2>
            <ul class="space-y-2">
                ${items
                    .map(
                        (item) => `
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">${item.quantity} × ${escapeHtml(item.product.name)}</span>
                            <span class="font-medium text-slate-900">${formatCurrency(item.quantity * item.product.price)}</span>
                        </li>
                    `
                    )
                    .join('')}
            </ul>
            <div class="flex items-center justify-between border-t border-slate-100 pt-4 text-base font-semibold text-slate-900">
                <span>Total</span>
                <span>${formatCurrency(cart.total)}</span>
            </div>
        </div>
    `;
}

function wireForm() {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();

        const formData = new FormData(form);
        const shippingAddress = {};
        FIELDS.concat([{ name: 'notes' }]).forEach((field) => {
            const value = formData.get(field.name)?.toString().trim();
            if (value) shippingAddress[field.name] = value;
        });

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Procesando…';

        try {
            const result = await apiFetch('/cart/checkout', {
                method: 'POST',
                body: JSON.stringify({ shipping_address: shippingAddress }),
            });

            window.refreshCartBadge?.();

            if (result.payment?.checkout_url) {
                window.location.href = result.payment.checkout_url;
                return;
            }

            // Order was created but the payment link couldn't be generated —
            // send them to the order where they can retry payment.
            window.location.href = `/mis-pedidos/${result.order.id}`;
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }

            // The backend still creates the order even when generating the
            // Mercado Pago link fails (e.g. 502) — send the customer to it
            // instead of stranding them on the form with a raw error.
            const orderId = error instanceof ApiError ? error.data?.order?.id : null;
            if (orderId) {
                showToast('Tu pedido se creó, pero no pudimos generar el link de pago. Reintentá desde el pedido.', 'error');
                window.location.href = `/mis-pedidos/${orderId}`;
                return;
            }

            const errorEl = document.getElementById('checkout-form-error');
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo procesar el checkout.';
                errorEl.classList.remove('hidden');
            }
            submitButton.disabled = false;
            submitButton.textContent = 'Continuar al pago';
        }
    });
}

export async function init() {
    if (!requireAuth()) return;

    const container = document.getElementById('checkout-content');
    if (!container) return;

    try {
        const cart = await apiFetch('/cart');
        const items = cart.cart?.items || [];

        if (!items.length) {
            window.location.href = '/carrito';
            return;
        }

        container.innerHTML = formHtml() + summaryHtml(cart);
        wireForm();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo cargar el checkout.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', () => window.location.reload());
    }
}
