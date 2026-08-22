import { apiFetch, ApiError, requireAuth, getUser } from '../api';
import { formatCurrency, formatDate, escapeHtml, orderStatusBadge, paymentStatusBadge, errorState, placeholderImage, showToast } from '../ui';

function itemRow(item) {
    const product = item.product || {};
    const image = (product.images || []).find((i) => i.is_primary) || (product.images || [])[0];
    return `
        <div class="flex items-center gap-4 py-3">
            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                ${
                    image
                        ? `<img src="/storage/${image.image}" alt="" class="h-full w-full object-cover">`
                        : placeholderImage('h-6 w-6')
                }
            </div>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-sm font-medium text-slate-900">${escapeHtml(product.name || 'Producto')}</p>
                <p class="text-xs text-slate-500">${item.quantity} × ${formatCurrency(item.unit_price)}</p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-slate-900">${formatCurrency(item.quantity * item.unit_price)}</p>
        </div>
    `;
}

function addressBlock(address) {
    if (!address) {
        return '<p class="text-sm text-slate-500">Sin datos de entrega.</p>';
    }
    return `
        <dl class="space-y-1 text-sm text-slate-700">
            <p class="font-medium text-slate-900">${escapeHtml(address.recipient_name)}</p>
            <p>${escapeHtml(address.phone)}</p>
            <p>${escapeHtml(address.address)}${address.number ? ' ' + escapeHtml(address.number) : ''}</p>
            <p>${escapeHtml(address.city)}, ${escapeHtml(address.state)} (${escapeHtml(address.postal_code)})</p>
            ${address.notes ? `<p class="text-slate-500">Obs: ${escapeHtml(address.notes)}</p>` : ''}
        </dl>
    `;
}

function actionsBlock(order) {
    const currentUser = getUser();
    const isOwner = currentUser && order.user_id === currentUser.id;

    if (order.status !== 'pending' || !isOwner) {
        return '';
    }

    const paymentStatus = order.latest_payment?.status;
    const canPay = !paymentStatus || ['pending', 'in_process', 'rejected', 'cancelled'].includes(paymentStatus);

    return `
        <div class="flex flex-col gap-2">
            ${canPay ? '<button type="button" id="pay-order" class="btn-primary btn-block">Pagar ahora</button>' : ''}
            <button type="button" id="cancel-order" class="btn-danger btn-block">Cancelar pedido</button>
        </div>
    `;
}

function renderOrder(order) {
    return `
        <div class="space-y-4 lg:col-span-2">
            <div class="surface p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">Pedido #${order.id}</h1>
                        <p class="text-sm text-slate-500">${formatDate(order.created_at)}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        ${orderStatusBadge(order.status)}
                        ${paymentStatusBadge(order.latest_payment?.status)}
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    ${(order.items || []).map(itemRow).join('')}
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-base font-semibold text-slate-900">
                    <span>Total</span>
                    <span>${formatCurrency(order.total)}</span>
                </div>
            </div>

            <div class="surface p-6">
                <h2 class="mb-3 text-base font-semibold text-slate-900">Datos de entrega</h2>
                ${addressBlock(order.shipping_address)}
            </div>
        </div>

        <div class="surface h-fit space-y-4 p-6">
            <h2 class="text-base font-semibold text-slate-900">Acciones</h2>
            <div id="order-actions">${actionsBlock(order)}</div>
            <p id="order-action-error" class="field-error hidden"></p>
        </div>
    `;
}

async function loadOrder(orderId) {
    const container = document.getElementById('order-detail');
    if (!container) return;

    try {
        const data = await apiFetch(`/orders/${orderId}`);
        container.innerHTML = renderOrder(data.order);
        wireActions(orderId);
    } catch (error) {
        const message =
            error instanceof ApiError && error.status === 404
                ? 'No encontramos este pedido.'
                : error instanceof ApiError
                  ? error.message
                  : 'No se pudo cargar el pedido.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', () => loadOrder(orderId));
    }
}

function wireActions(orderId) {
    const errorEl = document.getElementById('order-action-error');

    document.getElementById('pay-order')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        errorEl?.classList.add('hidden');

        try {
            const data = await apiFetch(`/orders/${orderId}/pay`, { method: 'POST' });
            window.location.href = data.payment.checkout_url;
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'No se pudo generar el link de pago.';
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            button.disabled = false;
        }
    });

    document.getElementById('cancel-order')?.addEventListener('click', async (event) => {
        if (!window.confirm('¿Seguro que querés cancelar este pedido?')) return;

        const button = event.currentTarget;
        button.disabled = true;
        errorEl?.classList.add('hidden');

        try {
            await apiFetch(`/orders/${orderId}/cancel`, { method: 'POST' });
            showToast('Pedido cancelado.', 'success');
            loadOrder(orderId);
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'No se pudo cancelar el pedido.';
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            button.disabled = false;
        }
    });
}

export function init() {
    if (!requireAuth()) return;
    const orderId = document.body.dataset.orderId;
    if (!orderId) return;
    loadOrder(orderId);
}
