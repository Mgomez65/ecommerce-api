import { apiFetch, ApiError, requireStaff } from '../api';
import { formatCurrency, formatDate, escapeHtml, orderStatusBadge, errorState, emptyState } from '../ui';

function summaryCard(label, value) {
    return `
        <div class="surface p-5">
            <p class="text-sm text-slate-500">${escapeHtml(label)}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">${value}</p>
        </div>
    `;
}

async function loadSummary() {
    const container = document.getElementById('admin-summary');
    if (!container) return;

    try {
        const data = await apiFetch('/dashboard/summary');
        const pendingCount = data.orders_by_status?.pending || 0;

        container.innerHTML = [
            summaryCard('Ingresos (completados)', formatCurrency(data.total_revenue)),
            summaryCard('Pedidos totales', data.orders_count),
            summaryCard('Pedidos pendientes', pendingCount),
            summaryCard('Productos', data.products_count),
        ].join('');
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudo cargar el resumen.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadSummary);
    }
}

async function loadLowStock() {
    const container = document.getElementById('admin-low-stock');
    if (!container) return;

    try {
        const data = await apiFetch('/dashboard/low-stock');
        const products = data.products || [];

        if (!products.length) {
            container.innerHTML = emptyState('Sin alertas de stock.');
            return;
        }

        container.innerHTML = products
            .map(
                (p) => `
                <a href="/admin/productos/${p.id}/editar" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50">
                    <span class="text-slate-700">${escapeHtml(p.name)}</span>
                    <span class="badge bg-red-100 text-red-700">${p.stock} / min. ${p.stock_minimo}</span>
                </a>
            `
            )
            .join('');
    } catch (error) {
        container.innerHTML = errorState('No se pudo cargar el stock bajo.');
        container.querySelector('[data-retry]')?.addEventListener('click', loadLowStock);
    }
}

async function loadTopProducts() {
    const container = document.getElementById('admin-top-products');
    if (!container) return;

    try {
        const data = await apiFetch('/dashboard/top-products');
        const products = data.products || [];

        if (!products.length) {
            container.innerHTML = emptyState('Todavía no hay ventas registradas.');
            return;
        }

        container.innerHTML = products
            .map(
                (p) => `
                <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm">
                    <span class="text-slate-700">${escapeHtml(p.name)}</span>
                    <span class="font-medium text-slate-900">${p.total_sold} vendidos</span>
                </div>
            `
            )
            .join('');
    } catch (error) {
        container.innerHTML = errorState('No se pudieron cargar los productos más vendidos.');
        container.querySelector('[data-retry]')?.addEventListener('click', loadTopProducts);
    }
}

async function loadRecentOrders() {
    const container = document.getElementById('admin-recent-orders');
    if (!container) return;

    try {
        const data = await apiFetch('/dashboard/recent-orders');
        const orders = data.orders || [];

        if (!orders.length) {
            container.innerHTML = emptyState('Todavía no hay pedidos.');
            return;
        }

        container.innerHTML = `
            <table class="w-full min-w-[480px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                        <th class="pb-2 font-medium">Pedido</th>
                        <th class="pb-2 font-medium">Cliente</th>
                        <th class="pb-2 font-medium">Estado</th>
                        <th class="pb-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    ${orders
                        .map(
                            (order) => `
                            <tr>
                                <td class="py-2.5">
                                    <a href="/admin/pedidos?highlight=${order.id}" class="font-medium text-brand-700 hover:underline">#${order.id}</a>
                                    <div class="text-xs text-slate-400">${formatDate(order.created_at)}</div>
                                </td>
                                <td class="py-2.5 text-slate-600">${escapeHtml(order.user?.name || '—')}</td>
                                <td class="py-2.5">${orderStatusBadge(order.status)}</td>
                                <td class="py-2.5 text-right font-medium text-slate-900">${formatCurrency(order.total)}</td>
                            </tr>
                        `
                        )
                        .join('')}
                </tbody>
            </table>
        `;
    } catch (error) {
        container.innerHTML = errorState('No se pudieron cargar los pedidos recientes.');
        container.querySelector('[data-retry]')?.addEventListener('click', loadRecentOrders);
    }
}

export function init() {
    if (!requireStaff()) return;
    loadSummary();
    loadLowStock();
    loadTopProducts();
    loadRecentOrders();
}
