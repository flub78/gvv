/**
 * Playwright tests — Webhook HelloAsso (EF2)
 *
 * Tests HTTP sur l'endpoint public /paiements_en_ligne/helloasso_webhook/{club_id} :
 *  1. POST sans signature → HTTP 401
 *  2. POST avec signature invalide → HTTP 401
 *  3. POST avec eventType != 'Order' + signature invalide → HTTP 401
 *  4. POST avec JSON invalide + signature invalide → HTTP 401
 *  5. GET → HTTP 405 (Method Not Allowed)
 *  6. Endpoint accessible sans session (pas de redirection login)
 *  7. POST sans club_id dans l'URL → HTTP 400
 *
 * Le club_id est désormais passé dans l'URL (source fiable), et la vérification
 * HMAC est effectuée avant tout traitement du payload.
 * Sans secret webhook configuré ou avec une signature invalide → 401 systématique.
 *
 * Tous ces tests ne déclenchent pas de vrai paiement HelloAsso.
 * Ils vérifient uniquement le comportement HTTP de l'endpoint.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/paiements-en-ligne-webhook.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

// club_id=1 (Section Planeur — section de test standard)
const WEBHOOK_URL = '/index.php/paiements_en_ligne/helloasso_webhook/1';

const MINIMAL_ORDER_PAYLOAD = JSON.stringify({
    eventType: 'Order',
    data: {
        metadata: {
            type: 'provisionnement',
            gvv_transaction_id: 'playwright-test-nonexistent-' + Date.now(),
        },
        payments: [{ state: 'Authorized', amount: 1000 }],
    },
});

test.describe('EF2 — Webhook HelloAsso HTTP behavior', () => {

    // ── 1. Signature manquante → 401 ─────────────────────────────────────────
    // club_id est dans l'URL (fiable) ; HMAC vérifié avant tout traitement

    test('POST without X-Ha-Signature returns 401', async ({ request }) => {
        const response = await request.post(WEBHOOK_URL, {
            headers: { 'Content-Type': 'application/json' },
            data: MINIMAL_ORDER_PAYLOAD,
        });
        expect(response.status()).toBe(401);
        expect(response.url()).not.toContain('/auth/login');
    });

    // ── 2. Signature invalide → 401 ──────────────────────────────────────────

    test('POST with invalid X-Ha-Signature returns 401', async ({ request }) => {
        const payload = JSON.stringify({
            eventType: 'Order',
            data: {
                metadata: {
                    type: 'provisionnement',
                    gvv_transaction_id: 'playwright-invalid-sig-' + Date.now(),
                },
                payments: [{ state: 'Authorized', amount: 1000 }],
            },
        });

        const response = await request.post(WEBHOOK_URL, {
            headers: {
                'Content-Type': 'application/json',
                'X-Ha-Signature': 'sha256=invalidsignature0000000000000000000000000000000000000',
            },
            data: payload,
        });

        expect(response.status()).toBe(401);
        expect(response.url()).not.toContain('/auth/login');
    });

    // ── 3. eventType != 'Order' + signature invalide → 401 ──────────────────
    // La vérification HMAC précède le filtrage par eventType

    test('POST with non-Order eventType and invalid signature returns 401', async ({ request }) => {
        const payload = JSON.stringify({
            eventType: 'Payment',
            data: { id: 12345 },
        });

        const response = await request.post(WEBHOOK_URL, {
            headers: { 'Content-Type': 'application/json' },
            data: payload,
        });

        expect(response.status()).toBe(401);
    });

    test('POST with Form eventType and invalid signature returns 401', async ({ request }) => {
        const payload = JSON.stringify({
            eventType: 'Form',
            data: {},
        });

        const response = await request.post(WEBHOOK_URL, {
            headers: { 'Content-Type': 'application/json' },
            data: payload,
        });

        expect(response.status()).toBe(401);
    });

    // ── 4. JSON invalide + signature invalide → 401 ──────────────────────────
    // La vérification HMAC précède le décodage JSON

    test('POST with invalid JSON and invalid signature returns 401', async ({ request }) => {
        const response = await request.post(WEBHOOK_URL, {
            headers: { 'Content-Type': 'application/json' },
            data: 'not valid json {{{',
        });

        expect(response.status()).toBe(401);
    });

    // ── 5. GET → 405 ─────────────────────────────────────────────────────────

    test('GET returns 405 Method Not Allowed', async ({ request }) => {
        const response = await request.get(WEBHOOK_URL);
        expect(response.status()).toBe(405);
    });

    // ── 6. Accessible sans session (pas de redirection login) ────────────────

    test('POST is accessible without session — returns 401 not login redirect', async ({ page }) => {
        await page.goto('/index.php/auth/logout');
        await page.waitForLoadState('domcontentloaded');

        const response = await page.request.post(WEBHOOK_URL, {
            headers: { 'Content-Type': 'application/json' },
            data: JSON.stringify({ eventType: 'Payment', data: {} }),
        });

        // Endpoint public : pas de redirection vers /auth/login
        expect(response.url()).not.toContain('/auth/login');
        // Signature invalide → 401, pas de session → ne doit pas être 302 vers login
        expect([401, 405]).toContain(response.status());
    });

    // ── 7. Pas de club_id dans l'URL → 400 ───────────────────────────────────

    test('POST without club_id in URL returns 400', async ({ request }) => {
        const response = await request.post(
            '/index.php/paiements_en_ligne/helloasso_webhook',
            {
                headers: { 'Content-Type': 'application/json' },
                data: MINIMAL_ORDER_PAYLOAD,
            }
        );
        expect(response.status()).toBe(400);
    });

});
