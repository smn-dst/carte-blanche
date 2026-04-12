/**
 * Point d'entrée formulaire restaurant (création / édition).
 */
import { initGoogleAddressAutocomplete } from './google-address-autocomplete.js';
import { initAuctionDatetimePickers } from './auction-datetime-pickers.js';
import { initImageUploadRepeater } from './image-upload-repeater.js';
import { initAiDescriptionGenerator } from './ai-description-generator.js';

function boot() {
    const cfg = document.getElementById('restaurant-form-config');
    if (!cfg) {
        return;
    }

    const googleKey = cfg.dataset.googleMapsKey || '';
    if (googleKey) {
        initGoogleAddressAutocomplete(googleKey);
    }

    initAuctionDatetimePickers();
    initImageUploadRepeater();

    const genUrl = cfg.dataset.generateDescriptionUrl || '';
    if (genUrl) {
        initAiDescriptionGenerator(genUrl);
    }
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('turbo:load', boot);
