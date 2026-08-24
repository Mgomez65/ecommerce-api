import { apiFetch, ApiError } from '../api';
import { clearFormErrors, showFormErrors, showToast } from '../ui';

export function init() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    const email = params.get('email');

    const emailLabel = document.getElementById('reset-password-email');
    const form = document.getElementById('reset-password-form');
    const errorEl = document.getElementById('reset-password-error');

    if (!token || !email) {
        if (errorEl) {
            errorEl.textContent = 'El link no es válido. Pedí uno nuevo desde "Recuperar contraseña".';
            errorEl.classList.remove('hidden');
        }
        form?.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
        return;
    }

    if (emailLabel) emailLabel.textContent = `Para ${email}`;
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors();
        errorEl?.classList.add('hidden');

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            await apiFetch('/auth/reset-password', {
                method: 'POST',
                body: JSON.stringify({
                    token,
                    email,
                    password: formData.get('password'),
                    password_confirmation: formData.get('password_confirmation'),
                }),
            });

            showToast('Contraseña actualizada. Ya podés ingresar.', 'success');
            window.location.href = '/login';
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showFormErrors(error.errors);
            }
            if (errorEl) {
                errorEl.textContent = error instanceof ApiError ? error.message : 'No se pudo restablecer la contraseña.';
                errorEl.classList.remove('hidden');
            }
            submitButton.disabled = false;
        }
    });
}
