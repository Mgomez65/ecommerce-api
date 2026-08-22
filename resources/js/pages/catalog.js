import { apiFetch, ApiError } from '../api';
import { productCardHtml, errorState, emptyState, clearFormErrors, showFormErrors } from '../ui';

const FILTER_KEYS = ['search', 'category_id', 'min_price', 'max_price', 'page'];

function readFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const filters = {};
    FILTER_KEYS.forEach((key) => {
        const value = params.get(key);
        if (value) filters[key] = value;
    });
    return filters;
}

function filtersToQueryString(filters) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.set(key, value);
        }
    });
    return params.toString();
}

function flattenCategories(categories, depth = 0) {
    return categories.flatMap((cat) => [
        { id: cat.id, name: `${'— '.repeat(depth)}${cat.name}` },
        ...flattenCategories(cat.children || [], depth + 1),
    ]);
}

async function populateCategoryFilter(selectedId) {
    const select = document.getElementById('filter-category');
    if (!select) return;

    try {
        const data = await apiFetch('/categories');
        const flat = flattenCategories(data.categories || []);
        flat.forEach((cat) => {
            const option = document.createElement('option');
            option.value = String(cat.id);
            option.textContent = cat.name;
            if (selectedId && String(cat.id) === String(selectedId)) option.selected = true;
            select.appendChild(option);
        });
    } catch {
        // Non-critical — the rest of the catalog still works without it.
    }
}

function populateForm(filters) {
    const form = document.getElementById('filters-form');
    if (!form) return;
    form.querySelector('[name="search"]').value = filters.search || '';
    form.querySelector('[name="min_price"]').value = filters.min_price || '';
    form.querySelector('[name="max_price"]').value = filters.max_price || '';
}

function renderPagination(paginator) {
    const nav = document.getElementById('catalog-pagination');
    if (!nav) return;

    if (!paginator || paginator.last_page <= 1) {
        nav.innerHTML = '';
        return;
    }

    const filters = readFiltersFromUrl();
    const linkFor = (page) => `/catalogo?${filtersToQueryString({ ...filters, page })}`;

    const buttons = [];
    buttons.push(
        `<a href="${linkFor(paginator.current_page - 1)}" data-page-link class="btn-secondary btn-sm ${paginator.current_page <= 1 ? 'pointer-events-none opacity-40' : ''}">Anterior</a>`
    );
    buttons.push(`<span class="px-3 text-sm text-slate-600">Página ${paginator.current_page} de ${paginator.last_page}</span>`);
    buttons.push(
        `<a href="${linkFor(paginator.current_page + 1)}" data-page-link class="btn-secondary btn-sm ${paginator.current_page >= paginator.last_page ? 'pointer-events-none opacity-40' : ''}">Siguiente</a>`
    );

    nav.innerHTML = buttons.join('');
    nav.querySelectorAll('[data-page-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            navigateTo(new URL(link.href).search);
        });
    });
}

function navigateTo(search) {
    window.history.pushState({}, '', `/catalogo${search}`);
    loadProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function loadProducts() {
    const grid = document.getElementById('catalog-grid');
    const countLabel = document.getElementById('catalog-result-count');
    if (!grid) return;

    clearFormErrors();
    const filters = readFiltersFromUrl();
    populateForm(filters);

    grid.innerHTML = Array.from({ length: 8 })
        .map(
            () => `
            <div class="surface overflow-hidden">
                <div class="skeleton aspect-square w-full"></div>
                <div class="space-y-2 p-4">
                    <div class="skeleton h-4 w-3/4"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
            </div>
        `
        )
        .join('');
    if (countLabel) countLabel.textContent = 'Cargando productos…';

    try {
        const query = filtersToQueryString({ ...filters, active: 1, per_page: 12 });
        const data = await apiFetch(`/products?${query}`);
        const paginator = data.products;
        const products = paginator?.data || [];

        if (!products.length) {
            grid.innerHTML = emptyState('No encontramos productos', 'Probá cambiar la búsqueda o los filtros.');
            if (countLabel) countLabel.textContent = 'Sin resultados';
        } else {
            grid.innerHTML = products.map(productCardHtml).join('');
            if (countLabel) {
                countLabel.textContent = `${paginator.total} producto${paginator.total === 1 ? '' : 's'} encontrado${paginator.total === 1 ? '' : 's'}`;
            }
        }

        renderPagination(paginator);
    } catch (error) {
        if (error instanceof ApiError && error.status === 422) {
            showFormErrors(error.errors);
            grid.innerHTML = emptyState('Revisá los filtros', 'Alguno de los valores ingresados no es válido.');
            if (countLabel) countLabel.textContent = '';
            return;
        }

        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar los productos.';
        grid.innerHTML = errorState(message);
        if (countLabel) countLabel.textContent = '';
        grid.querySelector('[data-retry]')?.addEventListener('click', loadProducts);
    }
}

function wireFiltersForm() {
    const form = document.getElementById('filters-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const filters = {
            search: formData.get('search')?.toString().trim() || '',
            category_id: formData.get('category_id')?.toString() || '',
            min_price: formData.get('min_price')?.toString() || '',
            max_price: formData.get('max_price')?.toString() || '',
        };
        navigateTo(filtersToQueryString(filters) ? `?${filtersToQueryString(filters)}` : '');
    });

    document.getElementById('filters-clear')?.addEventListener('click', () => {
        form.reset();
        navigateTo('');
    });

    document.getElementById('filters-toggle')?.addEventListener('click', () => {
        document.getElementById('filters-panel')?.classList.toggle('hidden');
    });
}

export function init() {
    const initialFilters = readFiltersFromUrl();
    populateCategoryFilter(initialFilters.category_id);
    wireFiltersForm();
    loadProducts();

    window.addEventListener('popstate', loadProducts);
}
