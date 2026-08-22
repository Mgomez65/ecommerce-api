import { apiFetch, ApiError, requireStaff } from '../api';
import { formatCurrency, formatDate, escapeHtml, errorState, emptyState, showToast } from '../ui';

const STATUS_OPTIONS = ['pending', 'confirmed', 'completed', 'cancelled'];
const STATUS_LABELS = {
    pending: 'Pendiente',
    confirmed: 'Confirmado',
    completed: 'Completado',
    cancelled: 'Cancelado',
};

function statusSelect(order) {
    const isFinal = order.status === 'cancelled' || order.status === 'completed';
    return `
        <select data-status-select data-order-id="${order.id}" class="field-input w-auto py-1.5 text-xs" ${isFinal ? 'disabled' : ''}>
            ${STATUS_OPTIONS.map((s) => `<option value="${s}" ${s === order.status ? 'selected' : ''}>${STATUS_LABELS[s]}</option>`).join('')}
        </select>
    `;
}

function orderRow(order) {
    return `
        <tr data-order-row="${order.id}">
            <td class="py-3 pr-4 text-sm font-medium text-slate-900">#${order.id}</td>
            <td class="py-3 pr-4 text-sm text-slate-600">
                <div>${escapeHtml(order.user?.name || '—')}</div>
                <div class="text-xs text-slate-400">${escapeHtml(order.user?.email || '')}</div>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-500">${formatDate(order.created_at)}</td>
            <td class="py-3 pr-4">${statusSelect(order)}</td>
            <td class="py-3 pr-4 text-right text-sm font-medium text-slate-900">${formatCurrency(order.total)}</td>
        </tr>
    `;
}

async function loadOrders() {
    const container = document.getElementById('admin-orders-list');
    const statusFilter = document.getElementById('status-filter')?.value || '';
    if (!container) return;

    container.innerHTML = '<div class="skeleton h-64 w-full"></div>';

    try {
        const query = statusFilter ? `?status=${statusFilter}` : '';
        const data = await apiFetch(`/orders${query}`);
        const orders = data.orders || [];

        if (!orders.length) {
            container.innerHTML = emptyState('No hay pedidos con ese filtro.');
            return;
        }

        container.innerHTML = `
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                        <th class="p-4 pb-2 font-medium">Pedido</th>
                        <th class="pb-2 font-medium">Cliente</th>
                        <th class="pb-2 font-medium">Fecha</th>
                        <th class="pb-2 font-medium">Estado</th>
                        <th class="pb-2 pr-4 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 px-4">
                    ${orders.map(orderRow).join('')}
                </tbody>
            </table>
        `;
        wireStatusSelects();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar los pedidos.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadOrders);
    }
}

function wireStatusSelects() {
    document.querySelectorAll('[data-status-select]').forEach((select) => {
        const original = select.value;
        select.addEventListener('change', async () => {
            const orderId = select.dataset.orderId;
            const newStatus = select.value;
            select.disabled = true;

            try {
                await apiFetch(`/orders/${orderId}/status`, {
                    method: 'PATCH',
                    body: JSON.stringify({ status: newStatus }),
                });
                showToast(`Pedido #${orderId} actualizado a "${STATUS_LABELS[newStatus]}".`, 'success');
                loadOrders();
            } catch (error) {
                const message = error instanceof ApiError ? error.message : 'No se pudo actualizar el estado.';
                showToast(message, 'error');
                select.value = original;
                select.disabled = false;
            }
        });
    });
}

export function init() {
    if (!requireStaff()) return;
    document.getElementById('status-filter')?.addEventListener('change', loadOrders);
    loadOrders();
}
