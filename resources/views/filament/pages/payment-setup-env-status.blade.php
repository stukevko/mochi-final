<div class="space-y-6">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Globale API-Keys in der <code class="rounded bg-gray-500/15 px-1 py-0.5 text-xs">.env</code> gelten für die ganze Installation.
        Zusätzlich kannst du in <strong class="text-gray-900 dark:text-white">Zahlungen einrichten</strong> pro Zahlart Händler-Felder pflegen — was Checkout und Jobs konkret nutzen, steht in der jeweiligen Doku / Implementierung.
    </p>

    @if ($showDebugWarning ?? false)
        <div
            class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-950 dark:border-amber-400/35 dark:bg-amber-500/15 dark:text-amber-100"
            role="alert"
        >
            <strong class="font-semibold">Hinweis:</strong> Debug-Modus ist an und ein <strong>Stripe Live-Secret</strong> scheint gesetzt zu sein — in Produktion beides vermeiden.
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200/60 dark:border-white/10">
        <table class="w-full divide-y divide-gray-200/60 text-sm dark:divide-white/10">
            <thead class="bg-gray-50/80 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-900 dark:text-white">Anbieter</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-900 dark:text-white">Status</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-900 dark:text-white">Vorschau</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/40 bg-white/40 dark:divide-white/5 dark:bg-transparent">
                @foreach ($checks ?? [] as $row)
                    <tr>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            {{ $row['provider'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if (! empty($row['configured']))
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-500/25 dark:text-emerald-300 dark:ring-emerald-400/30"
                                >
                                    Gesetzt
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-500/15 px-2 py-0.5 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-400/25 dark:text-gray-400 dark:ring-gray-500/30"
                                >
                                    Leer
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                            {{ $row['masked'] ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-500">
        Shop-Kontext: <strong class="text-gray-700 dark:text-gray-300">{{ $shopName ?? config('app.name') }}</strong>
    </p>
</div>
