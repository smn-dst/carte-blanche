/**
 * Filtres enchères — auto-submit au changement de filtre.
 * La recherche texte est soumise avec un debounce de 400ms.
 * Le slider de prix soumet au relâchement (change event + debounce).
 *
 * Les inputs du drawer mobile sont isolés du formulaire (pas d'attribut form=)
 * et synchronisés manuellement vers le formulaire au clic sur "Appliquer".
 */

const form = document.getElementById('search-form');
if (form) {

    // ── Debounce utilitaire ──
    function debounce(fn, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    const submitForm = () => form.submit();
    const debouncedSubmit = debounce(submitForm, 400);

    // ── Selects → submit immédiat au changement ──
    form.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', submitForm);
    });

    // ── Champ texte → submit avec debounce ──
    const searchInput = form.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', debouncedSubmit);
    }

    // ── Sliders de prix → submit au relâchement ──
    const minPriceInput = form.querySelector('input[name="minPrice"]');
    const maxPriceInput = form.querySelector('input[name="maxPrice"]');

    if (minPriceInput) {
        minPriceInput.addEventListener('change', debouncedSubmit);
    }
    if (maxPriceInput) {
        maxPriceInput.addEventListener('change', debouncedSubmit);
    }

    // ── Drawer mobile ──
    const toggleBtn = document.getElementById('filters-toggle');
    const drawerCloseBtn = document.getElementById('filters-drawer-close');
    const overlay = document.getElementById('filters-overlay');
    const drawer = document.getElementById('filters-drawer');

    function openDrawer() {
        drawer?.classList.remove('translate-y-full');
        overlay?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer?.classList.add('translate-y-full');
        overlay?.classList.add('hidden');
        document.body.style.overflow = '';
    }

    toggleBtn?.addEventListener('click', openDrawer);
    drawerCloseBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);

    // ── Bouton "Appliquer" : copie les valeurs du drawer vers le formulaire puis soumet ──
    const applyBtn = document.getElementById('filters-drawer-apply');
    if (applyBtn && drawer) {
        applyBtn.addEventListener('click', () => {
            ['category', 'sort', 'minPrice', 'maxPrice', 'priceSort', 'revenueSort'].forEach(name => {
                const drawerEl = drawer.querySelector('[name="' + name + '"]');
                const formEl = form.querySelector('[name="' + name + '"]');
                if (drawerEl && formEl) {
                    formEl.value = drawerEl.value;
                }
            });
            form.submit();
        });
    }
}
