/**
 * Formate un prix en euros.
 */
export function formatPrice(price) {
    return Number(price).toLocaleString('fr-FR');
}

/**
 * Est-ce un écran mobile ?
 */
export function isMobileViewport(width) {
    return width < 768;
}

/**
 * Retourne le bon id de conteneur carte.
 */
export function getMapContainer(isMobile) {
    return isMobile ? 'map-mobile' : 'map';
}

/**
 * Vérifie qu'un GeoJSON est valide.
 */
export function isValidGeoJSON(geojson) {
    return Boolean(
        geojson &&
        geojson.type === 'FeatureCollection' &&
        Array.isArray(geojson.features)
    );
}