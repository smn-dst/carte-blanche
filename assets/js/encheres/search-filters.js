/**
 * Filtres enchères — auto-submit au changement de filtre.
 * La recherche texte est soumise avec un debounce de 400ms.
 * Le slider de prix soumet au relâchement (input event + debounce).
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

    // ── Bouton toggle filtres mobile ──
    const toggleBtn = document.getElementById('filters-toggle');
    const filtersRow = form.querySelector('.hidden.md\\:flex');
    const mobileSelects = form.querySelectorAll('.hidden.md\\:block');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            filtersRow?.classList.toggle('hidden');
            filtersRow?.classList.toggle('flex');
            mobileSelects.forEach(el => {
                el.classList.toggle('hidden');
                el.classList.toggle('block');
            });
        });
    }
}