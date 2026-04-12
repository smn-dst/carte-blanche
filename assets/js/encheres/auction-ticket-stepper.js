/**
 * Sélecteur de quantité de billets (page détail enchère).
 * Lit la config JSON #auction-ticket-config (générée côté Twig).
 */
import { parseAuctionTicketConfig } from './auction-ticket-config.js';

function readTicketConfig() {
    const el = document.getElementById('auction-ticket-config');
    if (!el) {
        return null;
    }

    return parseAuctionTicketConfig(el.textContent);
}

function boot() {
    const configEl = document.getElementById('auction-ticket-config');
    if (!configEl || configEl.dataset.stepperInit === '1') {
        return;
    }

    const config = readTicketConfig();
    if (!config) {
        return;
    }

    const ticketPrice = Number(config.ticketPrice);
    const maxCapacity = Number(config.maxCapacity);
    const ticketsSold = Number(config.ticketsSold);
    const maxPerOrder = Number(config.maxPerOrder) || 10;

    const remaining = Math.max(0, maxCapacity - ticketsSold);
    const maxQty = Math.min(remaining, maxPerOrder);

    const qtyDisplay = document.getElementById('qty-display');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus = document.getElementById('btn-plus');
    const hiddenQtyCart = document.getElementById('cart-quantity');

    if (!qtyDisplay || !subtotalEl || !totalEl || !btnMinus || !btnPlus) {
        return;
    }

    configEl.dataset.stepperInit = '1';

    let qty = 1;

    const updateDisplay = () => {
        qtyDisplay.textContent = String(qty);
        const formatted = (ticketPrice * qty).toLocaleString('fr-FR');
        subtotalEl.textContent = `${formatted} €`;
        totalEl.textContent = `${formatted} €`;
        btnMinus.disabled = qty <= 1;
        btnPlus.disabled = qty >= maxQty;

        if (hiddenQtyCart) {
            hiddenQtyCart.value = String(qty);
        }
    };

    btnMinus.addEventListener('click', () => {
        if (qty > 1) {
            qty -= 1;
            updateDisplay();
        }
    });

    btnPlus.addEventListener('click', () => {
        if (qty < maxQty) {
            qty += 1;
            updateDisplay();
        }
    });

    updateDisplay();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('turbo:load', boot);