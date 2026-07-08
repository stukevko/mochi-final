const STORAGE_KEY = 'mochi_cookie_consent_v1';

let turnstileRequested = false;

function readConfig() {
    const node = document.getElementById('mochi-consent-config');
    if (!node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return {};
    }
}

function applyConsent(level) {
    document.documentElement.dataset.cookieConsent = level;
    window.dispatchEvent(new CustomEvent('mochi-cookie-consent', { detail: { level } }));

    if (level === 'all') {
        activateMapRoots();
    } else {
        deactivateMapRoots();
    }
}

function hideBanner() {
    const banner = document.getElementById('mochi-cookie-banner');
    if (banner) {
        banner.classList.add('hidden');
        banner.setAttribute('aria-hidden', 'true');
    }
}

function showBanner() {
    const banner = document.getElementById('mochi-cookie-banner');
    if (banner) {
        banner.classList.remove('hidden');
        banner.setAttribute('aria-hidden', 'false');
    }
}

function activateMapRoots() {
    document.querySelectorAll('[data-consent-map-root]').forEach((root) => {
        const iframe = root.querySelector('iframe[data-consent-embed]');
        const embedWrap = root.querySelector('[data-consent-map-embed]');
        const placeholder = root.querySelector('[data-consent-map-placeholder]');

        if (!iframe || !embedWrap || !placeholder) {
            return;
        }

        const src = iframe.getAttribute('data-consent-embed');
        if (!src || iframe.dataset.consentActivated === '1') {
            return;
        }

        iframe.src = src;
        iframe.dataset.consentActivated = '1';
        placeholder.classList.add('hidden');
        embedWrap.classList.remove('hidden');
    });
}

function deactivateMapRoots() {
    document.querySelectorAll('[data-consent-map-root]').forEach((root) => {
        const iframe = root.querySelector('iframe[data-consent-embed]');
        const embedWrap = root.querySelector('[data-consent-map-embed]');
        const placeholder = root.querySelector('[data-consent-map-placeholder]');

        if (!iframe || !embedWrap || !placeholder) {
            return;
        }

        iframe.removeAttribute('src');
        iframe.dataset.consentActivated = '0';
        embedWrap.classList.add('hidden');
        placeholder.classList.remove('hidden');
    });
}

function ensureTurnstile() {
    const config = readConfig();
    if (!config.turnstile || turnstileRequested) {
        return;
    }

    turnstileRequested = true;

    if (typeof window.mochiTurnstileCallback !== 'function') {
        window.mochiTurnstileCallback = (token) => {
            window.dispatchEvent(new CustomEvent('mochi-turnstile', { detail: { token } }));
        };
    }

    if (document.querySelector('script[data-consent-turnstile-script]')) {
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.async = true;
    script.defer = true;
    script.dataset.consentTurnstileScript = '1';
    document.head.appendChild(script);
}

function requestOptionalMaps() {
    const level = document.documentElement.dataset.cookieConsent;

    if (level === 'all') {
        activateMapRoots();

        return;
    }

    window.__mochiPendingMaps = true;
    showBanner();
}

function bindConsentControls() {
    document.querySelectorAll('[data-consent-load-maps]').forEach((button) => {
        button.addEventListener('click', requestOptionalMaps);
    });

    const formRoot = document.querySelector('[data-consent-turnstile]');
    if (formRoot) {
        formRoot.addEventListener('focusin', ensureTurnstile, { once: true });
    }
}

function saveConsent(level) {
    try {
        localStorage.setItem(STORAGE_KEY, level);
    } catch {
        // Private browsing — Banner bleibt sichtbar, Seite funktioniert trotzdem.
    }

    applyConsent(level);
    hideBanner();
}

export function openCookieSettings() {
    showBanner();
}

export function initCookieConsent() {
    if (window.location.pathname.startsWith('/admin')) {
        return;
    }

    const banner = document.getElementById('mochi-cookie-banner');
    if (!banner) {
        return;
    }

    window.openCookieSettings = openCookieSettings;

    document.getElementById('mochi-cookie-accept-all')?.addEventListener('click', () => saveConsent('all'));
    document.getElementById('mochi-cookie-essential')?.addEventListener('click', () => saveConsent('essential'));

    bindConsentControls();

    let stored = null;
    try {
        stored = localStorage.getItem(STORAGE_KEY);
    } catch {
        stored = null;
    }

    if (stored === 'all' || stored === 'essential') {
        applyConsent(stored);
        hideBanner();

        return;
    }

    showBanner();
}

window.addEventListener('mochi-cookie-consent', (event) => {
    if (event.detail?.level === 'all' && window.__mochiPendingMaps) {
        activateMapRoots();
    }
});
