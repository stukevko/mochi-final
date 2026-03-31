@extends('layouts.app')

@section('title', 'Service & Hilfe · '.$siteName)

@push('meta')
    <meta name="description" content="Kontakt, Versand und häufige Fragen zu unserem Shop.">
@endpush

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-display text-2xl font-bold tracking-tight text-white">Service &amp; Hilfe</h1>
        <p class="mt-2 text-sm text-mochi-muted">
            Kontakt, Versand und häufige Fragen.
        </p>

        <nav class="mt-8 flex flex-wrap gap-2 text-sm">
            <a href="#kontakt" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 font-medium text-mochi-text transition hover:border-mochi-accent/30">Kundenservice</a>
            <a href="#versand" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 font-medium text-mochi-text transition hover:border-mochi-accent/30">Versand &amp; Lieferung</a>
            <a href="#faq" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 font-medium text-mochi-text transition hover:border-mochi-accent/30">FAQ</a>
        </nav>

        <section id="kontakt" class="mt-12 scroll-mt-28 border-t border-white/10 pt-10">
            <h2 class="text-lg font-semibold text-white">Kundenservice</h2>
            <p class="mt-3 text-sm leading-relaxed text-mochi-muted">
                Bei Fragen zu Bestellungen, Produkten oder Lieferungen erreichen Sie uns per E-Mail.
            </p>
            @if (filled($shopContactEmail ?? null))
                <p class="mt-4">
                    <a
                        href="mailto:{{ $shopContactEmail }}"
                        class="font-medium text-mochi-accent underline underline-offset-2 hover:text-mochi-accent-strong"
                    >
                        {{ $shopContactEmail }}
                    </a>
                </p>
            @else
                <p class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                    Bitte eine Kontakt-E-Mail in den Admin-<strong>Einstellungen</strong> hinterlegen (<code>shop_email</code>).
                </p>
            @endif
        </section>

        <section id="versand" class="mt-12 scroll-mt-28 border-t border-white/10 pt-10">
            <h2 class="text-lg font-semibold text-white">Versand &amp; Lieferung</h2>
            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-relaxed text-mochi-muted">
                <li>Versand innerhalb Deutschlands; Lieferzeiten und Kosten je nach Bestellung.</li>
                <li>Nach Zahlungseingang erfolgt der Versand schnellstmöglich.</li>
            </ul>
        </section>

        <section id="faq" class="mt-12 scroll-mt-28 border-t border-white/10 pt-10">
            <h2 class="text-lg font-semibold text-white">FAQ</h2>
            <dl class="mt-6 space-y-6">
                <div>
                    <dt class="text-sm font-semibold text-white">Wie bestelle ich?</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-mochi-muted">
                        Produkte wählen, Warenkorb, Checkout.
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-white">Widerruf?</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-mochi-muted">
                        <a href="{{ route('legal.widerruf') }}" class="font-medium text-mochi-accent hover:text-mochi-accent-strong">Widerruf</a>
                        ·
                        <a href="{{ route('legal.agb') }}" class="font-medium text-mochi-accent hover:text-mochi-accent-strong">AGB</a>
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
