import { apiFetch, ApiError, requireStaff } from '../api';
import { formatCurrency, escapeHtml, errorState, emptyState, placeholderImage, showToast } from '../ui';

let currentPage = 1;

function productRow(product) {
    const image = (product.images || []).find((i) => i.is_primary) || (product.images || [])[0];
    return `
        <div class="flex items-center gap-4 border-b border-slate-100 p-4 last:border-0" data-product-row="${product.id}">
            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                ${image ? `<img src="/storage/${image.image}" alt="" class="h-full w-full object-cover">` : placeholderImage('h-5 w-5')}
            </div>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-sm font-medium text-slate-900">${escapeHtml(product.name)}</p>
                <p class="text-xs text-slate-500">${escapeHtml(product.category?.name || 'Sin categoría')}</p>
            </div>
            <div class="hidden w-24 shrink-0 text-sm text-slate-600 sm:block">${formatCurrency(product.price)}</div>
            <div class="hidden w-20 shrink-0 text-sm text-slate-600 sm:block">Stock: ${product.stock}</div>
            <div class="w-24 shrink-0">
                <span class="badge ${product.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}">${product.active ? 'Activo' : 'Inactivo'}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="/admin/productos/${product.id}/editar" class="btn-secondary btn-sm">Editar</a>
                <button type="button" data-delete-product="${product.id}" class="btn-danger btn-sm">Eliminar</button>
            </div>
        </div>
    `;
}

function renderPagination(paginator) {
    const nav = document.getElementById('admin-products-pagination');
    if (!nav) return;

    if (!paginator || paginator.last_page <= 1) {
        nav.innerHTML = '';
        return;
    }

    nav.innerHTML = `
        <button type="button" data-page="prev" class="btn-secondary btn-sm" ${paginator.current_page <= 1 ? 'disabled' : ''}>Anterior</button>
        <span class="px-3 text-sm text-slate-600">Página ${paginator.current_page} de ${paginator.last_page}</span>
        <button type="button" data-page="next" class="btn-secondary btn-sm" ${paginator.current_page >= paginator.last_page ? 'disabled' : ''}>Siguiente</button>
    `;

    nav.querySelector('[data-page="prev"]')?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        loadProducts();
    });
    nav.querySelector('[data-page="next"]')?.addEventListener('click', () => {
        currentPage += 1;
        loadProducts();
    });
}

async function loadProducts() {
    const container = document.getElementById('admin-products-list');
    if (!container) return;

    container.innerHTML = '<div class="skeleton h-64 w-full"></div>';

    try {
        const data = await apiFetch(`/products?per_page=15&page=${currentPage}`);
        const paginator = data.products;
        const products = paginator?.data || [];

        if (!products.length) {
            container.innerHTML = emptyState('Todavía no cargaste productos.');
            renderPagination(null);
            return;
        }

        container.innerHTML = products.map(productRow).join('');
        renderPagination(paginator);
        wireDeleteButtons();
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar los productos.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadProducts);
    }
}

function wireDeleteButtons() {
    document.querySelectorAll('[data-delete-product]').forEach((button) => {
        button.addEventListener('click', async () => {
            const productId = button.dataset.deleteProduct;
            if (!window.confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;

            button.disabled = true;
            try {
                await apiFetch(`/products/${productId}`, { method: 'DELETE' });
                showToast('Producto eliminado.', 'success');
                loadProducts();
            } catch (error) {
                const message = error instanceof ApiError ? error.message : 'No se pudo eliminar el producto.';
                showToast(message, 'error');
                button.disabled = false;
            }
        });
    });
}

export function init() {
    if (!requireStaff()) return;
    loadProducts();
}
