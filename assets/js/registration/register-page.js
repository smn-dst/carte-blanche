import { initPasswordToggles } from './password-toggle.js';

function boot() {
    initPasswordToggles();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('turbo:load', boot);
