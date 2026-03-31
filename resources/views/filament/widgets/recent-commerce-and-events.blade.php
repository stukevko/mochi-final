<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mochi Live-Feed
        </x-slot>

        <x-slot name="description">
            Dein Business im Griff.
        </x-slot>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Bestellungen --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2 px-0.5">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-gray-100">Shop</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Letzte 5</span>
                </div>

                @forelse ($latestOrders as $order)
                    <a
                        href="{{ $order['url'] }}"
                        class="group relative overflow-hidden rounded-2xl border border-gray-200/10 px-4 py-3.5 transition hover:border-orange-400/50"
                        style="background: linear-gradient(140deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.08); backdrop-filter: blur(8px);"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-gray-100">{{ $order['customer_name'] }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-400">{{ $order['number'] }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @php
                                        $pv = $order['payment_variant'] ?? 'muted';
                                        $badgeClass = match ($pv) {
                                            'paid' => 'bg-emerald-500/15 text-emerald-200 ring-emerald-400/30',
                                            'pending' => 'bg-amber-500/15 text-amber-100 ring-amber-400/35',
                                            'danger' => 'bg-rose-500/15 text-rose-100 ring-rose-400/35',
                                            default => 'bg-gray-500/10 text-gray-200 ring-gray-300/20',
                                        };
                                    @endphp
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset',
                                        $badgeClass,
                                    ])>
                                        {{ $order['payment_label'] }}
                                    </span>
                                </div>
                            </div>
                            <p class="shrink-0 text-base font-extrabold tabular-nums text-gray-100">{{ $order['total'] }}</p>
                        </div>
                    </a>
                @empty
                    <div
                        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300/20 bg-gray-900/20 px-6 py-14 text-center"
                    >
                        <x-heroicon-o-cube class="text-gray-300" style="width:56px; height:56px; opacity:.2;" />
                        <p class="mt-4 text-sm font-medium text-gray-300/80">Warte auf den ersten Kunden... 📦</p>
                    </div>
                @endforelse
            </div>

            {{-- Events --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2 px-0.5">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-gray-100">Kalender</h3>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Nächste 5</span>
                </div>

                @forelse ($latestEvents as $event)
                    @php
                        $wrapClasses = 'group relative overflow-hidden rounded-2xl border border-gray-200/10 px-4 py-3.5 transition hover:border-orange-400/50';
                    @endphp
                    @if (! empty($event['url']))
                        <a href="{{ $event['url'] }}" class="{{ $wrapClasses }}" style="background: linear-gradient(140deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.08); backdrop-filter: blur(8px);">
                            <div class="flex items-center gap-3">
                                <span
                                    class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-white/20"
                                    style="background-color: {{ $event['dot_color'] }}; box-shadow: 0 0 12px rgba(255,122,31,0.35);"
                                    aria-hidden="true"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-gray-100">{{ $event['title'] }}</p>
                                    <div class="mt-2">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold ring-1"
                                            style="background: rgba(255,122,31,0.2); color: #ffb073; border-color: rgba(255,122,31,0.35);"
                                        >
                                            {{ $event['date_badge'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @else
                        <div class="{{ $wrapClasses }} cursor-default" style="background: linear-gradient(140deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.08); backdrop-filter: blur(8px);">
                            <div class="flex items-center gap-3">
                                <span
                                    class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-white/20"
                                    style="background-color: {{ $event['dot_color'] }};"
                                    aria-hidden="true"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-gray-100">{{ $event['title'] }}</p>
                                    <div class="mt-2">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold ring-1"
                                            style="background: rgba(255,122,31,0.2); color: #ffb073; border-color: rgba(255,122,31,0.35);"
                                        >
                                            {{ $event['date_badge'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div
                        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300/20 bg-gray-900/20 px-6 py-14 text-center"
                    >
                        <x-heroicon-o-calendar-days class="text-gray-300" style="width:56px; height:56px; opacity:.2;" />
                        <p class="mt-4 text-sm font-medium text-gray-300/80">Noch keine Events in der Pipeline.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
