import { apiFetch, ApiError, setSession, isAuthenticated } from '../api';
import { clearFormErrors, showFormErrors, showToast } from '../ui';

export function init() {
    if (isAuthenticated()) {
        window.location.href = '/';
        return;
    }

    const form = document.getElementById('register-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        document.getElementById('register-form-error')?.classList.add('hidden');

        const formData = new FormData(form);
        const payload = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            password_confirmation: formData.get('password_confirmation'),
        };

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            await apiFetch('/auth/register', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            // Registration doesn't return a token — log in right away with
            // the same credentials for a one-step signup experience.
            const loginData = await apiFetch('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email: payload.email, password: payload.password }),
            });

            setSession(loginData.token, loginData.user);
            showToast(`¡Cuenta creada! Bienvenido, ${loginData.user.name}.`, 'success');
            window.location.href = '/';
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }
            const errorEl = document.getElementById('register-form-error');
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo crear la cuenta.';
                errorEl.classList.remove('hidden');
            }
            submitButton.disabled = false;
        }
    });
}
