import { apiFetch, ApiError } from '../api';
import { errorState, emptyState, escapeHtml } from '../ui';

function categoryCard(category) {
    const children = category.children || [];
    return `
        <div class="surface p-5">
            <a href="/catalogo?category_id=${category.id}" class="text-base font-semibold text-slate-900 hover:text-brand-700">
                ${escapeHtml(category.name)}
            </a>
            ${
                children.length
                    ? `<ul class="mt-3 space-y-1.5 border-t border-slate-100 pt-3">
                        ${children
                            .map(
                                (child) => `
                                <li>
                                    <a href="/catalogo?category_id=${child.id}" class="text-sm text-slate-600 hover:text-brand-700">
                                        ${escapeHtml(child.name)}
                                    </a>
                                </li>
                            `
                            )
                            .join('')}
                    </ul>`
                    : ''
            }
        </div>
    `;
}

async function loadCategories() {
    const container = document.getElementById('categories-list');
    if (!container) return;

    try {
        const data = await apiFetch('/categories');
        const categories = data.categories || [];

        if (!categories.length) {
            container.innerHTML = emptyState('Todavía no hay categorías cargadas.');
            return;
        }

        container.innerHTML = categories.map(categoryCard).join('');
    } catch (error) {
        const message = error instanceof ApiError ? error.message : 'No se pudieron cargar las categorías.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', loadCategories);
    }
}

export function init() {
    loadCategories();
}
