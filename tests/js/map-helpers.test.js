import { describe, it, expect } from 'vitest';
import {
    formatPrice,
    isMobileViewport,
    getMapContainer,
    isValidGeoJSON,
} from '../../assets/js/map/map-helpers.js';

describe('formatPrice', () => {
    it('formate un nombre', () => {
        const result = formatPrice(213009);
        expect(result).toContain('213');
    });

    it('gère zéro', () => {
        expect(formatPrice(0)).toBe('0');
    });

    it('gère un string', () => {
        const result = formatPrice('1500');
        expect(result).toContain('1');
        expect(result).toContain('500');
    });
});

describe('isMobileViewport', () => {
    it('true si petit écran', () => {
        expect(isMobileViewport(375)).toBe(true);
    });

    it('false si grand écran', () => {
        expect(isMobileViewport(1024)).toBe(false);
    });

    it('false pile à 768', () => {
        expect(isMobileViewport(768)).toBe(false);
    });
});

describe('getMapContainer', () => {
    it('map-mobile si mobile', () => {
        expect(getMapContainer(true)).toBe('map-mobile');
    });

    it('map si desktop', () => {
        expect(getMapContainer(false)).toBe('map');
    });
});

describe('isValidGeoJSON', () => {
    it('accepte un GeoJSON valide', () => {
        expect(isValidGeoJSON({ type: 'FeatureCollection', features: [] })).toBe(true);
    });

    it('refuse null', () => {
        expect(isValidGeoJSON(null)).toBe(false);
    });

    it('refuse sans features', () => {
        expect(isValidGeoJSON({ type: 'FeatureCollection' })).toBe(false);
    });
});