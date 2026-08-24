import '../css/app.css';
import { apiFetch, getUser, isAuthenticated, isStaff, clearSession } from './api';
import { showToast } from './ui';

function initMobileMenu() {
    const toggle = document.getElementById('nav-toggle');
    const menu = document.getElementById('mobile-menu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        toggle.setAttribute('aria-expanded', String(!isOpen));
    });
}

function initSearchForm() {
    document.querySelectorAll('[data-search-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const input = form.querySelector('input[name="search"]');
            const query = input?.value.trim();
            const url = new URL('/catalogo', window.location.origin);
            if (query) url.searchParams.set('search', query);
            window.location.href = url.toString();
        });
    });
}

function renderAuthArea() {
    const desktopSlot = document.getElementById('auth-area-desktop');
    const mobileSlot = document.getElementById('auth-area-mobile');
    const adminLinks = document.querySelectorAll('[data-admin-link]');

    const authed = isAuthenticated();
    const user = getUser();
    const staff = isStaff();

    adminLinks.forEach((el) => el.classList.toggle('hidden', !staff));

    const guestLinksDesktop = `
        <a href="/login" class="btn-ghost btn-sm">Ingresar</a>
        <a href="/registro" class="btn-primary btn-sm">Crear cuenta</a>
    `;
    const userLinksDesktop = `
        <div class="relative" data-user-menu>
            <button type="button" data-user-menu-toggle class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">${(user?.name || '?').charAt(0).toUpperCase()}</span>
                <span class="hidden sm:inline">${escapeName(user?.name)}</span>
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div data-user-menu-dropdown class="absolute right-0 z-20 mt-2 hidden w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-slate-200">
                <a href="/mis-pedidos" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Mis pedidos</a>
                ${staff ? '<a href="/admin" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Panel admin</a>' : ''}
                <button type="button" data-logout class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Cerrar sesión</button>
            </div>
        </div>
    `;

    if (desktopSlot) desktopSlot.innerHTML = authed ? userLinksDesktop : guestLinksDesktop;

    const guestLinksMobile = `
        <a href="/login" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Ingresar</a>
        <a href="/registro" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Crear cuenta</a>
    `;
    const userLinksMobile = `
        <a href="/mis-pedidos" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Mis pedidos</a>
        ${staff ? '<a href="/admin" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">Panel admin</a>' : ''}
        <button type="button" data-logout class="block w-full rounded-lg px-3 py-2 text-left text-base font-medium text-red-600 hover:bg-red-50">Cerrar sesión</button>
    `;
    if (mobileSlot) mobileSlot.innerHTML = authed ? userLinksMobile : guestLinksMobile;

    document.querySelectorAll('[data-logout]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                await apiFetch('/auth/logout', { method: 'POST' });
            } catch {
                // Ignore — we clear the local session regardless.
            }
            clearSession();
            showToast('Sesión cerrada.', 'success');
            window.location.href = '/';
        });
    });

    document.querySelectorAll('[data-user-menu-toggle]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const dropdown = btn.parentElement.querySelector('[data-user-menu-dropdown]');
            dropdown?.classList.toggle('hidden');
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('[data-user-menu-dropdown]').forEach((d) => d.classList.add('hidden'));
    });
}

function escapeName(name) {
    const div = document.createElement('div');
    div.textContent = name || 'Cuenta';
    return div.innerHTML;
}

async function refreshCartBadge() {
    const badges = document.querySelectorAll('[data-cart-count]');
    if (!badges.length) return;

    if (!isAuthenticated()) {
        badges.forEach((b) => b.classList.add('hidden'));
        return;
    }

    try {
        const data = await apiFetch('/cart');
        const count = (data.cart?.items || []).reduce((sum, item) => sum + item.quantity, 0);
        badges.forEach((b) => {
            b.textContent = String(count);
            b.classList.toggle('hidden', count === 0);
        });
    } catch {
        badges.forEach((b) => b.classList.add('hidden'));
    }
}

window.refreshCartBadge = refreshCartBadge;

const PAGE_MODULES = {
    home: () => import('./pages/home.js'),
    catalog: () => import('./pages/catalog.js'),
    categories: () => import('./pages/categories.js'),
    product: () => import('./pages/product.js'),
    cart: () => import('./pages/cart.js'),
    checkout: () => import('./pages/checkout.js'),
    login: () => import('./pages/login.js'),
    register: () => import('./pages/register.js'),
    'forgot-password': () => import('./pages/forgot-password.js'),
    'reset-password': () => import('./pages/reset-password.js'),
    orders: () => import('./pages/orders.js'),
    'order-detail': () => import('./pages/order-detail.js'),
    'admin-dashboard': () => import('./pages/admin-dashboard.js'),
    'admin-orders': () => import('./pages/admin-orders.js'),
    'admin-products': () => import('./pages/admin-products.js'),
    'admin-product-form': () => import('./pages/admin-product-form.js'),
};

async function initPageModule() {
    const page = document.body.dataset.page;
    const loader = page && PAGE_MODULES[page];
    if (!loader) return;

    try {
        const mod = await loader();
        mod.init?.();
    } catch (error) {
        console.error(`Error inicializando la página "${page}":`, error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initSearchForm();
    renderAuthArea();
    refreshCartBadge();
    initPageModule();
});

window.addEventListener('auth:changed', () => {
    renderAuthArea();
    refreshCartBadge();
});
