import { apiFetch, ApiError, isAuthenticated } from '../api';
import { formatCurrency, escapeHtml, errorState, placeholderImage, showToast } from '../ui';

function renderGallery(product) {
    const images = product.images || [];
    if (!images.length) {
        return `<div class="surface aspect-square overflow-hidden">${placeholderImage('h-20 w-20')}</div>`;
    }

    const sorted = [...images].sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0));
    const main = sorted[0];

    return `
        <div class="space-y-3">
            <div class="surface aspect-square overflow-hidden">
                <img id="product-main-image" src="/storage/${main.image}" alt="${escapeHtml(product.name)}" class="h-full w-full object-cover">
            </div>
            ${
                sorted.length > 1
                    ? `<div class="grid grid-cols-5 gap-2">
                        ${sorted
                            .map(
                                (img, i) => `
                                <button type="button" data-thumb="/storage/${img.image}" class="surface aspect-square overflow-hidden ${i === 0 ? 'ring-2 ring-brand-600' : ''}">
                                    <img src="/storage/${img.image}" alt="" class="h-full w-full object-cover">
                                </button>
                            `
                            )
                            .join('')}
                    </div>`
                    : ''
            }
        </div>
    `;
}

function renderDetail(product) {
    const outOfStock = Number(product.stock) <= 0;
    const unavailable = !product.active;
    const disableCart = outOfStock || unavailable;

    return `
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            ${renderGallery(product)}

            <div>
                ${product.category?.name ? `<span class="text-xs font-medium uppercase tracking-wide text-brand-600">${escapeHtml(product.category.name)}</span>` : ''}
                <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">${escapeHtml(product.name)}</h1>
                <p class="mt-3 text-3xl font-bold text-slate-900">${formatCurrency(product.price)}</p>

                <div class="mt-3">
                    ${
                        unavailable
                            ? '<span class="badge bg-slate-200 text-slate-700">No disponible</span>'
                            : outOfStock
                              ? '<span class="badge bg-red-100 text-red-700">Sin stock</span>'
                              : '<span class="badge bg-emerald-100 text-emerald-700">En stock</span>'
                    }
                </div>

                ${product.description ? `<p class="mt-5 whitespace-pre-line text-sm leading-relaxed text-slate-600">${escapeHtml(product.description)}</p>` : ''}

                <form id="add-to-cart-form" class="mt-6 flex flex-wrap items-center gap-3">
                    <label for="quantity" class="sr-only">Cantidad</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="${Math.max(1, product.stock)}" value="1" class="field-input w-20" ${disableCart ? 'disabled' : ''}>
                    <button type="submit" class="btn-primary" ${disableCart ? 'disabled' : ''}>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.694 2.591-7.171.055-.227-.1-.443-.34-.443H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                        Agregar al carrito
                    </button>
                </form>
                <p id="add-to-cart-error" class="field-error hidden"></p>
            </div>
        </div>
    `;
}

function wireGalleryThumbs() {
    document.querySelectorAll('[data-thumb]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mainImg = document.getElementById('product-main-image');
            if (mainImg) mainImg.src = btn.dataset.thumb;
            document.querySelectorAll('[data-thumb]').forEach((b) => b.classList.remove('ring-2', 'ring-brand-600'));
            btn.classList.add('ring-2', 'ring-brand-600');
        });
    });
}

function wireAddToCart(productId) {
    const form = document.getElementById('add-to-cart-form');
    const errorEl = document.getElementById('add-to-cart-error');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.classList.add('hidden');

        if (!isAuthenticated()) {
            const redirect = encodeURIComponent(window.location.pathname);
            window.location.href = `/login?redirect=${redirect}`;
            return;
        }

        const quantity = Number(new FormData(form).get('quantity')) || 1;
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            await apiFetch('/cart/items', {
                method: 'POST',
                body: JSON.stringify({ product_id: Number(productId), quantity }),
            });
            showToast('Producto agregado al carrito.', 'success');
            window.refreshCartBadge?.();
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'No se pudo agregar el producto.';
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        } finally {
            submitButton.disabled = false;
        }
    });
}

export async function init() {
    const container = document.getElementById('product-detail');
    const breadcrumb = document.getElementById('product-breadcrumb');
    const productId = document.body.dataset.productId;
    if (!container || !productId) return;

    try {
        const data = await apiFetch(`/products/${productId}`);
        const product = data.product;

        document.title = `${product.name} — Tienda`;
        if (breadcrumb) breadcrumb.textContent = product.name;

        container.innerHTML = renderDetail(product);
        wireGalleryThumbs();
        wireAddToCart(product.id);
    } catch (error) {
        const message =
            error instanceof ApiError && error.status === 404
                ? 'No encontramos este producto.'
                : 'No se pudo cargar el producto.';
        container.innerHTML = errorState(message);
        container.querySelector('[data-retry]')?.addEventListener('click', () => window.location.reload());
    }
}
