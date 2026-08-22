import { apiFetch, ApiError, requireStaff } from '../api';
import { escapeHtml, clearFormErrors, showFormErrors, showToast, placeholderImage } from '../ui';

function flattenCategories(categories, depth = 0) {
    return categories.flatMap((cat) => [
        { id: cat.id, name: `${'— '.repeat(depth)}${cat.name}` },
        ...flattenCategories(cat.children || [], depth + 1),
    ]);
}

function formHtml(product, categories) {
    const options = flattenCategories(categories)
        .map(
            (cat) =>
                `<option value="${cat.id}" ${product?.category_id === cat.id ? 'selected' : ''}>${escapeHtml(cat.name)}</option>`
        )
        .join('');

    return `
        <form id="product-form" class="surface space-y-5 p-6">
            <div>
                <label for="name" class="field-label">Nombre</label>
                <input type="text" id="name" name="name" required class="field-input" value="${escapeHtml(product?.name || '')}">
                <p class="field-error hidden" data-error-for="name"></p>
            </div>
            <div>
                <label for="description" class="field-label">Descripción</label>
                <textarea id="description" name="description" rows="4" class="field-input">${escapeHtml(product?.description || '')}</textarea>
                <p class="field-error hidden" data-error-for="description"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="price" class="field-label">Precio</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required class="field-input" value="${product?.price ?? ''}">
                    <p class="field-error hidden" data-error-for="price"></p>
                </div>
                <div>
                    <label for="category_id" class="field-label">Categoría</label>
                    <select id="category_id" name="category_id" required class="field-input">
                        <option value="">Elegir…</option>
                        ${options}
                    </select>
                    <p class="field-error hidden" data-error-for="category_id"></p>
                </div>
                <div>
                    <label for="stock" class="field-label">Stock</label>
                    <input type="number" id="stock" name="stock" min="0" required class="field-input" value="${product?.stock ?? 0}">
                    <p class="field-error hidden" data-error-for="stock"></p>
                </div>
                <div>
                    <label for="stock_minimo" class="field-label">Stock mínimo</label>
                    <input type="number" id="stock_minimo" name="stock_minimo" min="0" required class="field-input" value="${product?.stock_minimo ?? 0}">
                    <p class="field-error hidden" data-error-for="stock_minimo"></p>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" id="active" name="active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600" ${product?.active !== false ? 'checked' : ''}>
                Producto activo (visible en la tienda)
            </label>
            <p id="product-form-error" class="field-error hidden"></p>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">${product ? 'Guardar cambios' : 'Crear producto'}</button>
                <a href="/admin/productos" class="btn-ghost">Cancelar</a>
            </div>
        </form>
    `;
}

function imagesSectionHtml(product) {
    const images = product.images || [];
    return `
        <div class="surface mt-6 space-y-4 p-6">
            <h2 class="text-base font-semibold text-slate-900">Imágenes</h2>
            <div id="product-images-grid" class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                ${
                    images.length
                        ? images
                              .map(
                                  (img) => `
                                <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-100">
                                    <img src="/storage/${img.image}" alt="" class="h-full w-full object-cover">
                                    ${img.is_primary ? '<span class="badge absolute left-1 top-1 bg-brand-600 text-white">Principal</span>' : ''}
                                </div>
                            `
                              )
                              .join('')
                        : `<div class="col-span-full flex aspect-square max-w-[8rem] items-center justify-center rounded-lg bg-slate-100">${placeholderImage()}</div>`
                }
            </div>
            <form id="image-upload-form" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                <div>
                    <label for="image" class="field-label">Subir imagen</label>
                    <input type="file" id="image" name="image" accept="image/*" required class="text-sm">
                </div>
                <label class="mb-1 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_primary" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    Marcar como principal
                </label>
                <button type="submit" class="btn-secondary">Subir</button>
            </form>
            <p id="image-upload-error" class="field-error hidden"></p>
        </div>
    `;
}

function collectPayload(form) {
    const formData = new FormData(form);
    return {
        name: formData.get('name'),
        description: formData.get('description') || null,
        price: Number(formData.get('price')),
        stock: Number(formData.get('stock')),
        stock_minimo: Number(formData.get('stock_minimo')),
        category_id: Number(formData.get('category_id')),
        active: formData.get('active') === 'on',
    };
}

function wireForm(productId) {
    const form = document.getElementById('product-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        document.getElementById('product-form-error')?.classList.add('hidden');

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const payload = collectPayload(form);
            if (productId) {
                await apiFetch(`/products/${productId}`, { method: 'PUT', body: JSON.stringify(payload) });
                showToast('Producto actualizado.', 'success');
            } else {
                const created = await apiFetch('/products', { method: 'POST', body: JSON.stringify(payload) });
                showToast('Producto creado. Ahora podés subirle imágenes.', 'success');
                window.location.href = `/admin/productos/${created.product.id}/editar`;
                return;
            }
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }
            const errorEl = document.getElementById('product-form-error');
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo guardar el producto.';
                errorEl.classList.remove('hidden');
            }
        } finally {
            submitButton.disabled = false;
        }
    });
}

function wireImageUpload(productId) {
    const form = document.getElementById('image-upload-form');
    if (!form || !productId) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorEl = document.getElementById('image-upload-error');
        errorEl?.classList.add('hidden');

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            await apiFetch(`/products/${productId}/images`, {
                method: 'POST',
                body: new FormData(form),
            });
            showToast('Imagen agregada.', 'success');
            window.location.reload();
        } catch (error) {
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo subir la imagen.';
                errorEl.classList.remove('hidden');
            }
            submitButton.disabled = false;
        }
    });
}

export async function init() {
    if (!requireStaff()) return;

    const productId = document.body.dataset.productId || null;
    const container = document.getElementById('product-form-container');
    const title = document.getElementById('product-form-title');
    if (!container) return;

    try {
        const categoriesData = await apiFetch('/categories');
        let product = null;

        if (productId) {
            const data = await apiFetch(`/products/${productId}`);
            product = data.product;
            if (title) title.textContent = `Editar: ${product.name}`;
        } else if (title) {
            title.textContent = 'Nuevo producto';
        }

        container.innerHTML = formHtml(product, categoriesData.categories || []) + (product ? imagesSectionHtml(product) : '');
        wireForm(productId);
        wireImageUpload(productId);
    } catch (error) {
        container.innerHTML = `<p class="field-error">${error instanceof ApiError ? error.message : 'No se pudo cargar el formulario.'}</p>`;
    }
}
