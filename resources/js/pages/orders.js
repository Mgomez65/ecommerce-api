import { apiFetch, ApiError, requireAuth } from '../api';
import { formatCurrency, formatDate, orderStatusBadge, errorState, emptyState } from '../ui';

function orderRow(order) {
    const itemsCount = (order.items || []).reduce((sum, i) => sum + i.quantity, 0);
    return `
        <a href="/mis-pedidos/${order.id}" class="surface flex flex-wrap items-center justify-between gap-3 p-4 transition hover:shadow-md">
            <div>
                <p class="text-sm font-semibold text-slate-900">Pedido #${order.id}</p>
                <p class="text-xs text-slate-500">${formatDate(order.created_at)} · ${itemsCount} producto${itemsCount === 1 ? '' : 's'}</p>
            </div>
            <div class="flex items-center gap-3">
                ${orderStatusBadge(order.status)}
                <span class="text-sm font-semibold text-slate-900">${formatCurrency(order.total)}</span>
            </div>
        </a>
    `;
}

async function loadOrders() {
    const container = document.getElementById('orders-list');
    if (!container) return;

    try {
        const data = await apiFetch('/orders');
        const orders = data.orders || [];

        if (!orders.length) {
            container.innerHTML = emptyState('Todavía no hiciste ningún pedido', 'Cuando compres algo, lo vas a ver acá.');
            return;
        }

        container.innerHTML = orders.map(orderRow).join('');
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar tus pedidos.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadOrders);
    }
}

export function init() {
    if (!requireAuth()) return;
    loadOrders();
}
