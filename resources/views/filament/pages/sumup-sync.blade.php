<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Ein Bestand für Laden und Shop</x-slot>
            <x-slot name="description">
                Verkäufe an der SumUp-Kasse werden alle 5 Minuten automatisch vom Shop-Bestand abgezogen.
                Zugeordnet wird über die Artikelnummer (SKU) — sie muss in SumUp und im Shop gleich sein.
            </x-slot>

            <div class="flex flex-wrap items-center gap-4">
                @if (! $enabled)
                    <x-filament::badge color="danger">SumUp nicht eingerichtet</x-filament::badge>
                @elseif ($lastSyncedAt)
                    <x-filament::badge color="success">Aktiv</x-filament::badge>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Letzter Abgleich: {{ $lastSyncedAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }} Uhr
                    </span>
                @else
                    <x-filament::badge color="warning">Noch nicht gestartet</x-filament::badge>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Erst Bestände im Shop prüfen, dann starten — verbucht wird nur, was danach verkauft wird.
                    </span>
                @endif

                <x-filament::button wire:click="syncSalesNow" wire:loading.attr="disabled" wire:target="syncSalesNow" icon="heroicon-o-arrow-path">
                    {{ $lastSyncedAt ? 'Jetzt abgleichen' : 'Abgleich starten' }}
                </x-filament::button>
            </div>

            @if ($withoutSku > 0)
                <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">
                    {{ $withoutSku }} aktive Shop-Artikel haben keine Artikelnummer. Sie werden nur über den exakt gleichen Namen erkannt — bitte Artikelnummer nachtragen.
                </p>
            @endif

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Wichtig: SumUp erlaubt es Shops nicht, den Bestand in SumUp zu ändern. Online-Verkäufe senken deshalb nur den Bestand hier im Shop.
                Maßgeblich ist der Bestand im Shop — er kennt beide Verkaufswege.
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Artikel aus SumUp übernehmen</x-slot>
            <x-slot name="description">
                In SumUp unter „Artikel“ die Liste als CSV exportieren und hier hochladen. Name, Preis und Artikelnummer werden übernommen.
            </x-slot>

            <form wire:submit="importCatalog" class="space-y-4">
                <input
                    type="file"
                    wire:model="csv"
                    accept=".csv,text/csv,text/plain"
                    class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white dark:text-gray-200"
                />
                @error('csv') <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror

                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model="createMissing" class="mt-0.5 rounded border-gray-300 text-primary-600 dark:border-white/20 dark:bg-white/5" />
                    <span>Neue Artikel anlegen (Artikel, die der Shop noch nicht kennt)</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model="overwriteStock" class="mt-0.5 rounded border-gray-300 text-primary-600 dark:border-white/20 dark:bg-white/5" />
                    <span>
                        Bestand aus SumUp übernehmen
                        <span class="block text-gray-500 dark:text-gray-400">Nur für die Ersteinrichtung oder nach einer Inventur. Überschreibt den Shop-Bestand — Online-Verkäufe seit dem letzten Mal gehen dabei verloren.</span>
                    </span>
                </label>

                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="importCatalog,csv" icon="heroicon-o-arrow-up-tray">
                    Artikel übernehmen
                </x-filament::button>
            </form>
        </x-filament::section>

        @if ($drafts->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Neu aus der Kasse — noch nicht im Shop sichtbar</x-slot>
                <x-slot name="description">Diese Artikel wurden im Laden verkauft, waren dem Shop aber unbekannt. Bestand und Bild ergänzen, dann aktivieren.</x-slot>
                <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                    @foreach ($drafts as $draft)
                        <li class="flex items-center justify-between gap-4 py-2">
                            <span class="text-gray-900 dark:text-white">{{ $draft->name }} <span class="text-gray-500 dark:text-gray-400">{{ $draft->sku ? '· Art.-Nr. '.$draft->sku : '' }}</span></span>
                            <a href="{{ \App\Filament\Resources\ProductResource::getUrl('edit', ['record' => $draft->id]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Bearbeiten</a>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">Zuletzt verbuchte Ladenverkäufe</x-slot>
            @if ($recentSales->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Ladenverkäufe verbucht.</p>
            @else
                <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                    @foreach ($recentSales as $sale)
                        <li class="py-2">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-900 dark:text-white">{{ $sale->closed_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }} · {{ $sale->channel }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ \App\Support\MoneyFormatter::format($sale->total_cents / 100) }}</span>
                            </div>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                @foreach ((array) $sale->lines as $line)
                                    {{ abs((int) ($line['quantity'] ?? 0)) }}× {{ $line['name'] ?? '' }}{{ ! empty($line['draft']) ? ' (neu, Entwurf)' : '' }}{{ $loop->last ? '' : ', ' }}
                                @endforeach
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
