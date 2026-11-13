// Macht echte Screenshots der laufenden App (Demo-Daten) — nur für Vorschauen.
import { chromium, devices } from 'playwright';
import { mkdirSync } from 'node:fs';

const base = 'http://127.0.0.1:8008';
const slug = process.env.PRODUCT_SLUG;
const out = 'preview-out';
mkdirSync(out, { recursive: true });

const browser = await chromium.launch();

async function shot(page, name, opts = {}) {
    // Auf sichtbare Bilder warten (lazy-loading), damit die Vorschau der echten Ansicht entspricht.
    await page.evaluate(() => Promise.all([...document.images].map((img) => img.complete ? null : new Promise((r) => { img.onload = img.onerror = r; setTimeout(r, 5000); }))));
    await page.waitForTimeout(900);
    await page.screenshot({ path: `${out}/${name}.jpg`, type: 'jpeg', quality: 80, ...opts });
    console.log('ok', name);
}

async function safe(label, fn) {
    try { await fn(); } catch (e) { console.log('FAILED', label, String(e).slice(0, 300)); }
}

async function dismissCookies(page) {
    for (const label of [/Nur notwendige/i, /Alle akzeptieren/i]) {
        const btn = page.getByRole('button', { name: label }).first();
        if (await btn.isVisible().catch(() => false)) { await btn.click().catch(() => {}); break; }
    }
}

// ---------- Handy ----------
const phone = await browser.newContext({ ...devices['iPhone 14'], locale: 'de-DE' });
const m = await phone.newPage();

await safe('m-home', async () => { await m.goto(base + '/', { waitUntil: 'networkidle' }); await shot(m, '01-handy-start-mit-cookie-banner'); await dismissCookies(m); await shot(m, '02-handy-start'); });
await safe('m-shop', async () => {
    await m.goto(base + '/shop', { waitUntil: 'networkidle' }); await shot(m, '03-handy-shop');
    await m.getByRole('button', { name: 'Mehr' }).click(); await m.waitForTimeout(500); await shot(m, '03b-handy-mehr-menue');
});
await safe('m-product', async () => {
    await m.goto(base + '/product/' + slug, { waitUntil: 'networkidle' });
    await shot(m, '04-handy-produkt');
    await m.locator('.app-buybar button').click();
    await m.waitForTimeout(1500);
    await shot(m, '05-handy-in-den-warenkorb');
});
await safe('m-cart', async () => { await m.goto(base + '/cart', { waitUntil: 'networkidle' }); await shot(m, '06-handy-warenkorb'); });
await safe('m-checkout', async () => { await m.goto(base + '/checkout', { waitUntil: 'networkidle' }); await shot(m, '07-handy-checkout'); await shot(m, '08-handy-checkout-ganze-seite', { fullPage: true }); });
await safe('m-events', async () => { await m.goto(base + '/events', { waitUntil: 'networkidle' }); await shot(m, '09-handy-events'); });
await safe('m-offline', async () => { await m.goto(base + '/offline', { waitUntil: 'networkidle' }); await shot(m, '10-handy-offline'); });

// ---------- Desktop ----------
const desk = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'de-DE' });
const d = await desk.newPage();
await safe('d-home', async () => { await d.goto(base + '/', { waitUntil: 'networkidle' }); await dismissCookies(d); await shot(d, '11-desktop-start'); });
await safe('d-shop', async () => { await d.goto(base + '/shop', { waitUntil: 'networkidle' }); await shot(d, '12-desktop-shop'); });

// ---------- Admin ----------
async function login(page) {
    await page.goto(base + '/admin/login', { waitUntil: 'networkidle' });
    await page.locator('input[type="email"]').fill('admin@mochicards.test');
    await page.locator('input[type="password"]').fill('password');
    await page.locator('button[type="submit"]').first().click();
    await page.waitForURL((u) => !u.pathname.includes('login'), { timeout: 20000 });
    await page.waitForLoadState('networkidle');
}
await safe('d-admin', async () => {
    await login(d);
    await shot(d, '13-admin-dashboard');
    await d.goto(base + '/admin/orders', { waitUntil: 'networkidle' }); await shot(d, '14-admin-bestellungen');
    await d.goto(base + '/admin/sumup-abgleich', { waitUntil: 'networkidle' }); await shot(d, '15-admin-kasse-und-shop', { fullPage: true });
    await d.goto(base + '/admin/manage-merchant-settings', { waitUntil: 'networkidle' }); await shot(d, '16-admin-einstellungen');
    await d.goto(base + '/admin/zugangsdaten', { waitUntil: 'networkidle' }); await shot(d, '19-admin-zugangsdaten', { fullPage: true });
});
await safe('m-admin', async () => { await login(m); await shot(m, '17-handy-admin-dashboard'); await m.goto(base + '/admin/zugangsdaten', { waitUntil: 'networkidle' }); await shot(m, '20-handy-admin-zugangsdaten'); await m.goto(base + '/admin/orders', { waitUntil: 'networkidle' }); await shot(m, '18-handy-admin-bestellungen'); });

await browser.close();
// trigger 1794577253
// trigger 1794611366
// trigger 1794612232
