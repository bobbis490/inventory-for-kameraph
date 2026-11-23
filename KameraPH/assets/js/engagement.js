/**
 * KameraPH → Module 4 "For You" Engagement Bridge
 *
 * Sends lightweight engagement events to Module 4's interaction API.
 * Fire-and-forget: never blocks the page; swallowed silently if the
 * remote server is unreachable.
 *
 * EventTypes (UPPERCASE, as required by Module 4):
 *   VIEW   – product detail page opened
 *   CLICK  – product card clicked from listing / home
 *   CART   – reservation / add-to-cart action triggered
 *   SEARCH – search query submitted
 *
 * Usage:
 *   sendToForYou('VIEW',   listingId);
 *   sendToForYou('CLICK',  listingId);
 *   sendToForYou('CART',   listingId);
 *   sendToForYou('SEARCH', null, searchText);
 */

const KPH_ENGAGEMENT_API = 'https://kameraph-engagement-cheea5a0h7erh8ht.eastasia-01.azurewebsites.net/api';

function sendToForYou(eventType, listingId, searchTerm) {
    const headers = { 'Content-Type': 'application/json' };

    // Prefer the shared auth token (logged-in users)
    const token = localStorage.getItem('kph-auth-token');
    if (token) {
        headers.Authorization = 'Bearer ' + token;
    } else {
        // Guests: stable random session key, persisted in localStorage
        let key = localStorage.getItem('kph-session-key');
        if (!key) {
            key = crypto.randomUUID();
            localStorage.setItem('kph-session-key', key);
        }
        headers['X-Session-Key'] = key;
    }

    const body = { EventType: eventType };
    if (listingId)  body.ListingID  = listingId;
    if (searchTerm) body.SearchTerm = searchTerm;

    fetch(KPH_ENGAGEMENT_API + '/interactions.php', {
        method: 'POST',
        headers,
        body: JSON.stringify(body),
        keepalive: true,   // survives page navigations
    }).catch(() => {});    // silent – remote being down must never surface to user
}
