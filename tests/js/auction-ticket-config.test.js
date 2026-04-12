import { describe, it, expect } from 'vitest';
import { parseAuctionTicketConfig } from '../../assets/js/encheres/auction-ticket-config.js';

describe('parseAuctionTicketConfig', () => {
    it('retourne un objet pour un JSON valide', () => {
        const config = parseAuctionTicketConfig('{"ticketPrice":12,"maxCapacity":50}');
        expect(config).toEqual({ ticketPrice: 12, maxCapacity: 50 });
    });

    it('retourne null si vide', () => {
        expect(parseAuctionTicketConfig('')).toBeNull();
        expect(parseAuctionTicketConfig('   ')).toBeNull();
    });

    it('retourne null si JSON invalide', () => {
        expect(parseAuctionTicketConfig('{pas json')).toBeNull();
    });
});
