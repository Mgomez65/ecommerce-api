const TOKEN_KEY = 'ecommerce_token';
const USER_KEY = 'ecommerce_user';

export class ApiError extends Error {
    constructor(message, status, errors = {}, data = null) {
        super(message);
        this.status = status;
        this.errors = errors;
        this.data = data;
    }
}

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function getUser() {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

export function setSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    window.dispatchEvent(new Event('auth:changed'));
}

export function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    window.dispatchEvent(new Event('auth:changed'));
}

export function isAuthenticated() {
    return Boolean(getToken());
}

export function isStaff() {
    const user = getUser();
    return Boolean(user && (user.role === 'admin' || user.role === 'vendedor'));
}

/**
 * Redirect to /login when there's no token, preserving the current page as
 * the ?redirect= target. Call at the top of any page that requires auth.
 */
export function requireAuth() {
    if (!isAuthenticated()) {
        const redirect = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/login?redirect=${redirect}`;
        return false;
    }
    return true;
}

/**
 * Same as requireAuth(), but also requires admin/vendedor role. This is a
 * UX convenience only — the API itself is the real authorization boundary.
 */
export function requireStaff() {
    if (!requireAuth()) return false;
    if (!isStaff()) {
        window.location.href = '/';
        return false;
    }
    return true;
}

export async function apiFetch(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...options.headers,
    };

    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    let response;
    try {
        response = await fetch(`/api${path}`, { ...options, headers });
    } catch {
        throw new ApiError('No se pudo conectar con el servidor. Revisá tu conexión.', 0);
    }

    let data = null;
    const text = await response.text();
    if (text) {
        try {
            data = JSON.parse(text);
        } catch {
            data = null;
        }
    }

    if (response.status === 401) {
        clearSession();
    }

    if (!response.ok) {
        throw new ApiError(
            data?.message || 'Ocurrió un error inesperado. Intentá nuevamente.',
            response.status,
            data?.errors || {},
            data
        );
    }

    return data;
}
