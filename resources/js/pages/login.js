import { apiFetch, ApiError, setSession, isAuthenticated } from '../api';
import { clearFormErrors, showFormErrors, showToast } from '../ui';

function redirectTarget() {
    const params = new URLSearchParams(window.location.search);
    return params.get('redirect') || '/';
}

export function init() {
    if (isAuthenticated()) {
        window.location.href = redirectTarget();
        return;
    }

    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        document.getElementById('login-form-error')?.classList.add('hidden');

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const data = await apiFetch('/auth/login', {
                method: 'POST',
                body: JSON.stringify({
                    email: formData.get('email'),
                    password: formData.get('password'),
                }),
            });

            setSession(data.token, data.user);
            showToast(`¡Bienvenido, ${data.user.name}!`, 'success');
            window.location.href = redirectTarget();
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }
            const errorEl = document.getElementById('login-form-error');
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo iniciar sesión.';
                errorEl.classList.remove('hidden');
            }
            submitButton.disabled = false;
        }
    });
}
