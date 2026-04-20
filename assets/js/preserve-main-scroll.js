/**
 * Après un POST qui redirige vers la même URL (panier, favoris, etc.),
 * conserve la position de défilement du <main> pour éviter le retour en haut.
 */
const STORAGE_Y = '__cb_scrollY';
const STORAGE_PATH = '__cb_scrollPath';

function scrollContainer () {
    return document.querySelector('main');
}

function saveScroll (form) {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    if (form.method.toLowerCase() !== 'post') {
        return;
    }
    if (!form.hasAttribute('data-preserve-scroll')) {
        return;
    }
    const main = scrollContainer();
    const y = main ? main.scrollTop : window.scrollY;
    sessionStorage.setItem(STORAGE_Y, String(y));
    sessionStorage.setItem(STORAGE_PATH, window.location.pathname + window.location.search);
}

function restoreScroll () {
    const y = sessionStorage.getItem(STORAGE_Y);
    const path = sessionStorage.getItem(STORAGE_PATH);
    if (y === null || path === null) {
        return;
    }
    if (path !== window.location.pathname + window.location.search) {
        sessionStorage.removeItem(STORAGE_Y);
        sessionStorage.removeItem(STORAGE_PATH);
        return;
    }
    sessionStorage.removeItem(STORAGE_Y);
    sessionStorage.removeItem(STORAGE_PATH);
    const top = parseInt(y, 10);
    requestAnimationFrame(() => {
        const main = scrollContainer();
        if (main) {
            main.scrollTop = top;
        } else {
            window.scrollTo(0, top);
        }
    });
}

document.addEventListener('submit', (e) => {
    saveScroll(e.target);
}, true);

document.addEventListener('turbo:submit-start', (e) => {
    const form = e.detail?.formSubmission?.formElement;
    if (form) {
        saveScroll(form);
    }
});

document.addEventListener('DOMContentLoaded', restoreScroll);
document.addEventListener('turbo:load', restoreScroll);
