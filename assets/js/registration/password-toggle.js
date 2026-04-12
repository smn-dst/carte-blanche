/**
 * Bascule affichage mot de passe (icône œil).
 * Attend des boutons avec data-password-toggle, data-password-input (id), data-password-icon (id du <svg> ou <path> parent).
 */
export function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        if (btn.dataset.passwordToggleBound === '1') {
            return;
        }
        btn.addEventListener('click', () => {
            const inputId = btn.dataset.passwordInput;
            const iconId = btn.dataset.passwordIcon;
            const input = inputId ? document.getElementById(inputId) : null;
            const icon = iconId ? document.getElementById(iconId) : null;
            if (!input || !icon) {
                return;
            }
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const showPath =
                'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21';
            const hidePath1 = 'M15 12a3 3 0 11-6 0 3 3 0 016 0z';
            const hidePath2 =
                'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z';
            icon.innerHTML = isPassword
                ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${showPath}"/>`
                : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${hidePath1}"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${hidePath2}"/>`;
        });
        btn.dataset.passwordToggleBound = '1';
    });
}
