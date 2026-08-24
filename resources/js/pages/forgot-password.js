import { apiFetch, ApiError, isAuthenticated } from '../api';
import { clearFormErrors, showFormErrors } from '../ui';

export function init() {
    if (isAuthenticated()) {
        window.location.href = '/';
        return;
    }

    const form = document.getElementById('forgot-password-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        document.getElementById('forgot-password-error')?.classList.add('hidden');
        document.getElementById('forgot-password-success')?.classList.add('hidden');

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const data = await apiFetch('/auth/forgot-password', {
                method: 'POST',
                body: JSON.stringify({ email: formData.get('email') }),
            });

            const successEl = document.getElementById('forgot-password-success');
            if (successEl) {
                successEl.textContent = data.message;
                successEl.classList.remove('hidden');
            }
            form.reset();
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }
            const errorEl = document.getElementById('forgot-password-error');
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo enviar el link.';
                errorEl.classList.remove('hidden');
            }
        } finally {
            submitButton.disabled = false;
        }
    });
}
