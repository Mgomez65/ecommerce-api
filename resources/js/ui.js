export function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(Number(value) || 0);
}

export function formatDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

export function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) {
        return;
    }

    const colors = {
        success: 'bg-emerald-600',
        error: 'bg-red-600',
        info: 'bg-slate-900',
    };

    const toast = document.createElement('div');
    toast.className = `${colors[type] || colors.info} pointer-events-auto rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg`;
    toast.style.animation = 'fade-in .2s ease-out';
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity .3s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

const ORDER_STATUS_MAP = {
    pending: ['Pendiente', 'bg-amber-100 text-amber-800'],
    confirmed: ['Confirmado', 'bg-blue-100 text-blue-800'],
    completed: ['Completado', 'bg-emerald-100 text-emerald-800'],
    cancelled: ['Cancelado', 'bg-red-100 text-red-800'],
};

const PAYMENT_STATUS_MAP = {
    pending: ['Pago pendiente', 'bg-amber-100 text-amber-800'],
    in_process: ['Pago en proceso', 'bg-amber-100 text-amber-800'],
    approved: ['Pago aprobado', 'bg-emerald-100 text-emerald-800'],
    rejected: ['Pago rechazado', 'bg-red-100 text-red-800'],
    cancelled: ['Pago cancelado', 'bg-red-100 text-red-800'],
    refunded: ['Reembolsado', 'bg-slate-200 text-slate-800'],
    charged_back: ['Contracargo', 'bg-red-100 text-red-800'],
};

export function orderStatusBadge(status) {
    const [label, classes] = ORDER_STATUS_MAP[status] || [status, 'bg-slate-100 text-slate-800'];
    return `<span class="badge ${classes}">${label}</span>`;
}

export function paymentStatusBadge(status) {
    if (!status) {
        return '';
    }
    const [label, classes] = PAYMENT_STATUS_MAP[status] || [status, 'bg-slate-100 text-slate-800'];
    return `<span class="badge ${classes}">${label}</span>`;
}

export function skeletonCard() {
    return `
        <div class="surface overflow-hidden">
            <div class="skeleton aspect-square w-full"></div>
            <div class="space-y-2 p-4">
                <div class="skeleton h-4 w-3/4"></div>
                <div class="skeleton h-4 w-1/2"></div>
            </div>
        </div>
    `;
}

export function placeholderImage(sizeClasses = 'h-12 w-12') {
    return `
        <div class="flex h-full w-full items-center justify-center text-slate-300">
            <svg class="${sizeClasses}" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 4.5h18M3 4.5a1.5 1.5 0 0 0-1.5 1.5v12a1.5 1.5 0 0 0 1.5 1.5h18a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 21 4.5" /></svg>
        </div>
    `;
}

export function primaryImageUrl(product) {
    const images = product.images || [];
    const primary = images.find((i) => i.is_primary) || images[0];
    return primary ? `/storage/${primary.image}` : null;
}

export function productCardHtml(product) {
    const img = primaryImageUrl(product);
    const outOfStock = Number(product.stock) <= 0;
    return `
        <a href="/productos/${product.id}" class="surface group flex flex-col overflow-hidden transition hover:shadow-md">
            <div class="relative aspect-square w-full overflow-hidden bg-slate-100">
                ${
                    img
                        ? `<img src="${img}" alt="${escapeHtml(product.name)}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">`
                        : placeholderImage()
                }
                ${outOfStock ? '<span class="badge absolute left-2 top-2 bg-slate-900/80 text-white">Sin stock</span>' : ''}
                ${!product.active ? '<span class="badge absolute left-2 top-2 bg-slate-900/80 text-white">No disponible</span>' : ''}
            </div>
            <div class="flex flex-1 flex-col gap-1 p-4">
                ${product.category?.name ? `<span class="text-xs font-medium uppercase tracking-wide text-brand-600">${escapeHtml(product.category.name)}</span>` : ''}
                <h3 class="line-clamp-2 text-sm font-medium text-slate-900">${escapeHtml(product.name)}</h3>
                <p class="mt-auto pt-2 text-base font-bold text-slate-900">${formatCurrency(product.price)}</p>
            </div>
        </a>
    `;
}

export function clearFormErrors(scope = document) {
    scope.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

export function showFormErrors(errors, scope = document) {
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const key = field.split('.').pop();
        const el = scope.querySelector(`[data-error-for="${key}"]`);
        if (el) {
            el.textContent = Array.isArray(messages) ? messages[0] : messages;
            el.classList.remove('hidden');
        }
    });
}

export function errorState(message, retryLabel = 'Reintentar') {
    return `
        <div class="alert-error col-span-full">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="flex-1">
                <p>${escapeHtml(message)}</p>
                <button type="button" data-retry class="btn-secondary btn-sm mt-3">${escapeHtml(retryLabel)}</button>
            </div>
        </div>
    `;
}

export function emptyState(title, description = '') {
    return `
        <div class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <svg class="mb-3 h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <p class="font-medium text-slate-900">${escapeHtml(title)}</p>
            ${description ? `<p class="mt-1 text-sm text-slate-500">${escapeHtml(description)}</p>` : ''}
        </div>
    `;
}
