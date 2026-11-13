/**
 * App-Shell: macht den Shop auf Handy/Tablet/Desktop installierbar und app-artig.
 *  - registriert den Service Worker (nur Production-Build, nicht bei `npm run dev`)
 *  - markiert <html> mit .is-standalone, wenn als installierte App geöffnet
 *  - merkt sich das Install-Event, damit ein Button `[data-app-install]` es auslösen kann
 */
export function initAppShell() {
    const html = document.documentElement;

    const standalone =
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: window-controls-overlay)').matches ||
        window.navigator.standalone === true;
    html.classList.toggle('is-standalone', standalone);

    if ('serviceWorker' in navigator && import.meta.env.PROD && window.isSecureContext) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
                /* Kein SW (z. B. privater Modus) — Seite funktioniert normal weiter. */
            });
        });
    }

    let deferredPrompt = null;
    const syncInstallButtons = () => {
        document.querySelectorAll('[data-app-install]').forEach((btn) => {
            btn.hidden = !deferredPrompt;
        });
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        syncInstallButtons();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        syncInstallButtons();
    });

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest?.('[data-app-install]');
        if (!btn || !deferredPrompt) return;
        deferredPrompt.prompt();
        await deferredPrompt.userChoice.catch(() => null);
        deferredPrompt = null;
        syncInstallButtons();
    });

    document.addEventListener('livewire:navigated', syncInstallButtons);
}
