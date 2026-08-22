import { apiFetch, ApiError } from '../api';
import { productCardHtml, errorState, emptyState, escapeHtml } from '../ui';

async function loadCategories() {
    const container = document.getElementById('home-categories');
    if (!container) return;

    try {
        const data = await apiFetch('/categories');
        const categories = (data.categories || []).slice(0, 6);

        if (!categories.length) {
            container.innerHTML = emptyState('Todavía no hay categorías cargadas.');
            return;
        }

        container.innerHTML = categories
            .map(
                (cat) => `
                <a href="/catalogo?category_id=${cat.id}" class="surface flex items-center justify-center px-3 py-6 text-center text-sm font-medium text-slate-700 transition hover:shadow-md hover:text-brand-700">
                    ${escapeHtml(cat.name)}
                </a>
            `
            )
            .join('');
    } catch (error) {
        container.innerHTML = errorState('No se pudieron cargar las categorías.');
        wireRetry(container, loadCategories);
    }
}

async function loadFeaturedProducts() {
    const container = document.getElementById('home-products');
    if (!container) return;

    try {
        const data = await apiFetch('/products?active=1&per_page=8');
        const products = data.products?.data || [];

        if (!products.length) {
            container.innerHTML = emptyState('Todavía no hay productos publicados.');
            return;
        }

        container.innerHTML = products.map(productCardHtml).join('');
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar los productos.';
        container.innerHTML = errorState(message);
        wireRetry(container, loadFeaturedProducts);
    }
}

function wireRetry(container, loader) {
    container.querySelector('[data-retry]')?.addEventListener('click', loader);
}

export function init() {
    loadCategories();
    loadFeaturedProducts();
}
