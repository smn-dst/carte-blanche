function openFilters() {
    const drawer = document.getElementById('filters-drawer');
    const overlay = document.getElementById('filters-overlay');
    if (!drawer || !overlay) {
        return;
    }
    drawer.classList.remove('translate-y-full');
    overlay.classList.remove('hidden');
}

function closeFilters() {
    const drawer = document.getElementById('filters-drawer');
    const overlay = document.getElementById('filters-overlay');
    if (!drawer || !overlay) {
        return;
    }
    drawer.classList.add('translate-y-full');
    overlay.classList.add('hidden');
}

document.addEventListener('click', (e) => {
    if (e.target.closest('#filters-toggle')) {
        e.preventDefault();
        openFilters();
        return;
    }
    if (e.target.closest('#filters-overlay')) {
        closeFilters();
        return;
    }
    if (e.target.closest('#filters-drawer-close')) {
        closeFilters();
        return;
    }
    if (e.target.closest('#filters-drawer-apply')) {
        closeFilters();
    }
});
