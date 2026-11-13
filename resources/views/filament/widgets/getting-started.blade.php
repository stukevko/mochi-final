<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Erste Schritte — {{ $doneCount }} von {{ $totalCount }} erledigt</x-slot>
        <x-slot name="description">Von oben nach unten durchgehen. Erledigtes hakt sich von selbst ab. Sind alle Pflichtschritte fertig, verschwindet diese Liste.</x-slot>

        <ol class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($steps as $index => $step)
                <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:gap-4">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-success-500/15 text-success-600 dark:text-success-400' => $step['done'],
                        'bg-primary-500/15 text-primary-600 dark:text-primary-400' => ! $step['done'],
                    ])>
                        @if ($step['done'])
                            <x-filament::icon icon="heroicon-m-check" class="h-5 w-5" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p @class(['font-medium', 'text-gray-500 line-through dark:text-gray-400' => $step['done'], 'text-gray-950 dark:text-white' => ! $step['done']])>
                            {{ $step['title'] }}
                            @if ($step['optional'])
                                <span class="ml-1 text-xs font-normal text-gray-500 no-underline dark:text-gray-400">(freiwillig)</span>
                            @endif
                        </p>
                        @unless ($step['done'])
                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $step['text'] }}</p>
                        @endunless
                    </div>
                    @unless ($step['done'])
                        <x-filament::button tag="a" :href="$step['url']" size="sm" :color="$step['optional'] ? 'gray' : 'primary'" class="shrink-0">
                            {{ $step['action'] }}
                        </x-filament::button>
                    @endunless
                </li>
            @endforeach
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
