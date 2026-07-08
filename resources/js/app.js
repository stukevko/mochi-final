import './bootstrap';
import './mochi-calendar';
import { initCookieConsent } from './cookie-consent';

/**
 * Dezentes Parallax auf große Hintergrund-Bokeh-Kreise (Glow-Up).
 */
function initMochiParallaxOrbs() {
    const root = document.getElementById('mochi-parallax-root');
    if (!root) return;

    if (root.dataset.mochiBackgroundAnimations === '0') {
        return;
    }

    const orbs = root.querySelectorAll('[data-mochi-parallax]');
    if (!orbs.length) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let ticking = false;
    const update = () => {
        const y = window.scrollY || 0;
        orbs.forEach((el) => {
            const factor = parseFloat(el.getAttribute('data-mochi-parallax') || '0');
            const damped = factor * 0.58;
            el.style.transform = `translate3d(0, ${y * damped}px, 0)`;
        });
        ticking = false;
    };

    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        },
        { passive: true },
    );
    update();
}

function initMochiMobileNav() {
    const header = document.getElementById('mochi-site-header');
    const toggle = document.getElementById('mochi-nav-toggle');
    const nav = document.getElementById('mochi-site-nav');
    if (!header || !toggle || !nav) {
        return;
    }

    const mq = window.matchMedia('(min-width: 768px)');

    const setClosed = () => {
        header.classList.remove('mochi-header--nav-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Menü öffnen');
    };

    toggle.addEventListener('click', () => {
        if (mq.matches) {
            return;
        }
        header.classList.toggle('mochi-header--nav-open');
        const open = header.classList.contains('mochi-header--nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
    });

    nav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!mq.matches) {
                setClosed();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (mq.matches) {
            setClosed();
        }
    });
}

function initMochiHeaderScroll() {
    const header = document.getElementById('mochi-site-header');
    if (!header) {
        return;
    }

    const threshold = 10;
    let ticking = false;

    const update = () => {
        const solid = (window.scrollY || 0) > threshold;
        header.classList.toggle('mochi-header--scrolled', solid);
        ticking = false;
    };

    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        },
        { passive: true },
    );
    update();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initMochiParallaxOrbs();
        initMochiHeaderScroll();
        initMochiMobileNav();
        initCookieConsent();
    });
} else {
    initMochiParallaxOrbs();
    initMochiHeaderScroll();
    initMochiMobileNav();
    initCookieConsent();
}

const TOAST_DEFAULT_MS = 3200;

window.showShopToast = function showShopToast(message, variant = 'success') {
    const root = document.getElementById('global-shop-toast');
    const inner = document.getElementById('global-shop-toast-inner');
    if (!root || !inner) {
        return;
    }

    inner.textContent = message || '';
    root.classList.remove('toast-success', 'toast-error', 'toast-info', 'show');
    root.classList.add(
        variant === 'error' ? 'toast-error' : variant === 'info' ? 'toast-info' : 'toast-success',
    );

    requestAnimationFrame(() => {
        root.classList.add('show');
    });

    window.clearTimeout(window.__shopToastHide);
    window.__shopToastHide = window.setTimeout(() => {
        root.classList.remove('show');
    }, TOAST_DEFAULT_MS);
};

window.addEventListener('shop-toast', (e) => {
    const p = e.detail;
    if (!p || typeof p !== 'object') {
        return;
    }
    const msg = String(p.message ?? '');
    const t = String(p.type ?? 'success');
    window.showShopToast(msg, t === 'error' ? 'error' : t === 'info' ? 'info' : 'success');
});

document.addEventListener('livewire:navigate', () => {
    document.body.classList.add('lw-navigating-fast');
});

document.addEventListener('livewire:navigated', () => {
    document.body.classList.remove('lw-navigating-fast');
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) {
        return;
    }

    Alpine.data('checkoutAddressSuggest', (suggestUrl) => ({
        zipSuggestions: [],
        streetSuggestions: [],
        loadingZip: false,
        loadingStreet: false,
        suggestUrl,
        livewire() {
            const root = this.$el?.closest('[wire\\:id]');
            const id = root?.getAttribute('wire:id');
            if (!id || typeof window.Livewire === 'undefined') {
                return null;
            }

            return window.Livewire.find(id);
        },
        async onZipInput(ev) {
            const q = ev.target.value.trim();
            if (q.length < 4) {
                this.zipSuggestions = [];
                return;
            }
            this.loadingZip = true;
            try {
                const r = await fetch(`${this.suggestUrl}?type=postcode&q=${encodeURIComponent(q)}`);
                const j = await r.json();
                this.zipSuggestions = j.suggestions || [];
            } catch {
                this.zipSuggestions = [];
            } finally {
                this.loadingZip = false;
            }
        },
        pickZip(s) {
            const lw = this.livewire();
            if (lw) {
                lw.set('zip', s.postcode ?? '');
                lw.set('city', s.city ?? '');
            }
            this.zipSuggestions = [];
        },
        async onStreetInput(ev) {
            const q = ev.target.value.trim();
            const lw = this.livewire();
            const city =
                (lw && typeof lw.get === 'function' ? lw.get('city') : undefined) ??
                (lw?.city ?? '');
            if (q.length < 2 || !city || String(city).trim().length < 2) {
                this.streetSuggestions = [];
                return;
            }
            this.loadingStreet = true;
            try {
                const r = await fetch(
                    `${this.suggestUrl}?type=street&q=${encodeURIComponent(q)}&city=${encodeURIComponent(String(city))}`,
                );
                const j = await r.json();
                this.streetSuggestions = j.suggestions || [];
            } catch {
                this.streetSuggestions = [];
            } finally {
                this.loadingStreet = false;
            }
        },
        pickStreet(s) {
            const lw = this.livewire();
            if (lw) {
                lw.set('street', s.street || s.label);
            }
            this.streetSuggestions = [];
        },
    }));
});

function setupCartFeedback() {
    window.addEventListener('cartUpdated', () => {
        const wrap = document.querySelector('.mochi-cart-icon-wrap');
        if (wrap) {
            wrap.classList.remove('mochi-cart-radial-active');
            void wrap.offsetWidth;
            wrap.classList.add('mochi-cart-radial-active');
            window.setTimeout(() => {
                wrap.classList.remove('mochi-cart-radial-active');
            }, 920);
        }

        const badge = document.getElementById('cart-count-badge');
        if (badge) {
            badge.classList.remove('cart-bump');
            badge.classList.remove('cart-ping');
            void badge.offsetWidth;
            badge.classList.add('cart-bump');
            badge.classList.add('cart-ping');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCartFeedback);
} else {
    setupCartFeedback();
}
