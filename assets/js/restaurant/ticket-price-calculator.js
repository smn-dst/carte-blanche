/**
 * Auto-calcule le prix du ticket en fonction du prix demandé.
 *
 * askingPrice < 100 000 €     → ticketPrice = 50 €
 * askingPrice 100 000-300 000 → ticketPrice = 100 €
 * askingPrice 300 000-500 000 → ticketPrice = 200 €
 * askingPrice > 500 000 €     → ticketPrice = 350 €
 */

function computeTicketPrice(askingPrice) {
    if (askingPrice < 100_000) return 50;
    if (askingPrice < 300_000) return 100;
    if (askingPrice <= 500_000) return 200;
    return 350;
}

function parseMoneyInput(raw) {
    // Supprime les espaces (séparateurs de milliers FR) et remplace la virgule par un point
    const cleaned = raw.replace(/[\s\u00A0\u202F]/g, '').replace(',', '.');
    const value = parseFloat(cleaned);
    return isNaN(value) ? null : value;
}

export function initTicketPriceCalculator() {
    const askingInput = document.getElementById('restaurant_form_askingPrice');
    const ticketInput = document.getElementById('restaurant_form_ticketPrice');

    if (!askingInput || !ticketInput) return;

    function update() {
        const asking = parseMoneyInput(askingInput.value);
        if (asking === null || asking <= 0) return;
        ticketInput.value = computeTicketPrice(asking);
    }

    askingInput.addEventListener('input', update);
    askingInput.addEventListener('change', update);

    // Déclenche au chargement si askingPrice est déjà renseigné (mode édition)
    if (askingInput.value.trim() !== '') {
        update();
    }
}
