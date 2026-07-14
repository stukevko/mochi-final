<x-filament-widgets::widget class="fi-wi-quick-links">
    <x-filament::section>
        <x-slot name="heading">
            Schnell loslegen
        </x-slot>
        <x-slot name="description">
            Häufige Aufgaben nach dem Login — Events, Shop-Renner auf der Startseite und News.
        </x-slot>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-filament::button
                :href="$eventCreateUrl"
                tag="a"
                class="!h-auto !justify-start !py-4"
                color="primary"
                icon="heroicon-o-plus-circle"
            >
                <span class="flex flex-col items-start gap-0.5 text-left">
                    <span class="font-semibold">Neues Event anlegen</span>
                    <span class="text-xs font-normal opacity-90">
                        Turnier oder Termin im Kalender &amp; Feed
                    </span>
                </span>
            </x-filament::button>

            <x-filament::button
                :href="$siteSettingsUrl"
                tag="a"
                class="!h-auto !justify-start !py-4"
                color="gray"
                :outlined="true"
                icon="heroicon-o-shopping-bag"
            >
                <span class="flex flex-col items-start gap-0.5 text-left">
                    <span class="font-semibold">Shop-Renner ändern</span>
                    <span class="text-xs font-normal opacity-80">
                        Bild, Text &amp; Link im Hero (Website-Einstellungen)
                    </span>
                </span>
            </x-filament::button>

            <x-filament::button
                :href="$postCreateUrl"
                tag="a"
                class="!h-auto !justify-start !py-4 sm:col-span-2 xl:col-span-1"
                color="gray"
                :outlined="true"
                icon="heroicon-o-newspaper"
            >
                <span class="flex flex-col items-start gap-0.5 text-left">
                    <span class="font-semibold">News schreiben</span>
                    <span class="text-xs font-normal opacity-80">Neuen Beitrag erstellen &amp; veröffentlichen</span>
                </span>
            </x-filament::button>
        </div>

        <div
            class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-200 pt-4 dark:border-white/10"
        >
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Mehr:</span>
            @if (filled($profileUrl))
                <x-filament::link :href="$profileUrl" icon="heroicon-o-shield-check">
                    MFA &amp; Profil
                </x-filament::link>
            @endif
            <x-filament::link :href="$aboutPageUrl" icon="heroicon-o-user-group">Über uns</x-filament::link>
            <x-filament::link :href="$eventsIndexUrl" icon="heroicon-o-calendar-days">Alle Events</x-filament::link>
            <x-filament::link :href="$calendarPageUrl" icon="heroicon-o-calendar">Kalender-Ansicht</x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
