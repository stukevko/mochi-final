@if (! request()->is('admin*'))
    <div
        id="mochi-cookie-banner"
        class="hidden fixed inset-x-0 bottom-0 z-[100] border-t border-white/15 bg-[#080c12]/95 p-4 shadow-[0_-12px_40px_-12px_rgba(0,0,0,0.85)] backdrop-blur-xl supports-[backdrop-filter]:bg-[#080c12]/90 sm:bottom-4 sm:mx-auto sm:max-w-3xl sm:rounded-2xl sm:border sm:px-6 sm:py-5"
        role="dialog"
        aria-labelledby="mochi-cookie-title"
        aria-describedby="mochi-cookie-desc"
        aria-hidden="true"
    >
        <div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0 pr-1">
                <p id="mochi-cookie-title" class="text-sm font-semibold text-white">Cookies &amp; Datenschutz</p>
                <p id="mochi-cookie-desc" class="mt-1.5 text-xs leading-relaxed text-mochi-muted sm:text-sm">
                    Wir setzen <strong class="font-medium text-mochi-text">technisch notwendige</strong> Cookies für
                    Warenkorb, Checkout, Sitzung und Sicherheit (z.&nbsp;B. CSRF). Auf der Kontaktseite nutzen wir
                    Cloudflare Turnstile gegen Spam (lädt erst bei Formular-Nutzung). Schriftarten werden von unserem
                    Server geladen — dafür ist keine Einwilligung nötig. Mit „Alle akzeptieren“ erlaubst du zusätzlich die
                    eingebettete Google-Maps-Karte. Deine Auswahl speichern wir lokal im Browser. Details in unserer
                    <a href="{{ route('legal.datenschutz') }}" class="font-medium text-mochi-coral underline-offset-2 hover:underline">Datenschutzerklärung</a>.
                </p>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <button
                    type="button"
                    id="mochi-cookie-essential"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-medium text-mochi-text transition hover:border-white/25 hover:bg-white/10"
                >
                    Nur notwendige
                </button>
                <button
                    type="button"
                    id="mochi-cookie-accept-all"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-mochi-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-mochi-coral"
                >
                    Alle akzeptieren
                </button>
            </div>
        </div>
    </div>
@endif
