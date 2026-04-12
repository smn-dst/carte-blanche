/**
 * Parse le JSON de configuration des billets (bloc #auction-ticket-config).
 */
export function parseAuctionTicketConfig(raw) {
    if (!raw || !String(raw).trim()) {
        return null;
    }
    try {
        return JSON.parse(String(raw));
    } catch {
        return null;
    }
}
