/**
 * Google Places Autocomplete pour adresse restaurant et lieu d'enchère.
 * @param {string} apiKey Clé API Google Maps (vide = noop)
 */
let googleAddressListenersBound = false;

export function initGoogleAddressAutocomplete(apiKey) {
    if (!apiKey) {
        return;
    }

    const autocompleteScriptSelector = 'script[data-google-places-script="restaurant-address"]';

    const bindAutocomplete = (inputSelector, latitudeSelector, longitudeSelector, types) => {
        const input = document.querySelector(inputSelector);
        const latitudeInput = document.querySelector(latitudeSelector);
        const longitudeInput = document.querySelector(longitudeSelector);

        if (!input || !latitudeInput || !longitudeInput) {
            return;
        }

        if (input.dataset.googleAutocompleteBound === '1') {
            return;
        }

        const autocomplete = new window.google.maps.places.Autocomplete(input, {
            fields: ['formatted_address', 'geometry'],
            types,
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            const location = place.geometry && place.geometry.location ? place.geometry.location : null;

            if (!location) {
                return;
            }

            if (place.formatted_address) {
                input.value = place.formatted_address;
            }

            latitudeInput.value = String(location.lat());
            longitudeInput.value = String(location.lng());
        });

        input.addEventListener('input', () => {
            latitudeInput.value = '';
            longitudeInput.value = '';
        });

        input.dataset.googleAutocompleteBound = '1';
    };

    const initializeAutocomplete = () => {
        if (!(window.google && window.google.maps && window.google.maps.places)) {
            return;
        }

        bindAutocomplete(
            'input[data-address-autocomplete-target="address"]',
            'input[data-address-autocomplete-target="latitude"]',
            'input[data-address-autocomplete-target="longitude"]',
            ['address']
        );

        bindAutocomplete(
            'input[data-auction-location-autocomplete-target="location"]',
            'input[data-auction-location-autocomplete-target="latitude"]',
            'input[data-auction-location-autocomplete-target="longitude"]',
            ['geocode']
        );
    };

    const loadGooglePlaces = () => {
        if (window.google && window.google.maps && window.google.maps.places) {
            initializeAutocomplete();
            return;
        }

        const existingScript = document.querySelector(autocompleteScriptSelector);
        if (existingScript) {
            if (existingScript.dataset.loaded === '1') {
                initializeAutocomplete();
            }
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
        script.async = true;
        script.defer = true;
        script.dataset.googlePlacesScript = 'restaurant-address';
        script.addEventListener('load', () => {
            script.dataset.loaded = '1';
            initializeAutocomplete();
        });

        document.head.appendChild(script);
    };

    loadGooglePlaces();
    if (!googleAddressListenersBound) {
        document.addEventListener('turbo:load', loadGooglePlaces);
        googleAddressListenersBound = true;
    }
}
