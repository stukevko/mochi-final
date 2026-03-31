<x-filament-panels::page>
    <div class="mochi-fc-page space-y-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Termine ziehen, um die Startzeit zu ändern (speichert sofort). Klick öffnet die Bearbeitung im seitlichen Panel.
            Archiv-Termine sind zur Übersicht sichtbar, aber abgeschwächt.
        </p>

        <div
            class="rounded-xl border border-white/10 bg-[#040712]/90 px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] backdrop-blur-sm dark:bg-[#040712]/95"
        >
            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Spielarten
            </p>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($gameLegend as $item)
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold shadow-sm"
                        style="{{ $item['badgeStyle'] }}"
                    >
                        {{ $item['label'] }}
                    </span>
                @endforeach
                <span
                    class="inline-flex items-center rounded-full border border-gray-500/50 bg-gray-500/20 px-3 py-1 text-xs font-semibold text-gray-200"
                >
                    Archiv
                </span>
            </div>
        </div>

        <div
            wire:ignore
            class="mochi-fc-host rounded-xl border border-white/10 bg-[#040712] p-2 shadow-[0_20px_50px_-36px_rgba(0,0,0,0.85)]"
        >
            <div id="mochi-event-calendar" class="min-h-[640px]"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        (function (events, livewireId, rescheduleBaseUrl) {
            let calendar = null;

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            }

            function livewire() {
                if (window.Livewire && livewireId && typeof window.Livewire.find === 'function') {
                    const byId = window.Livewire.find(livewireId);
                    if (byId) {
                        return byId;
                    }
                }
                const el = document.querySelector('[wire\\:id]');
                return el && el.__livewire ? el.__livewire : null;
            }

            function boot() {
                const el = document.getElementById('mochi-event-calendar');
                if (!el || typeof FullCalendar === 'undefined') {
                    return;
                }

                if (calendar) {
                    calendar.destroy();
                    calendar = null;
                }

                calendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    locale: 'de',
                    firstDay: 1,
                    events,
                    height: 'auto',
                    editable: true,
                    eventStartEditable: true,
                    eventDurationEditable: false,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek',
                    },
                    eventClick(info) {
                        info.jsEvent.preventDefault();
                        const lw = livewire();
                        if (!lw) {
                            return;
                        }
                        lw.mountAction('editCalendarEvent', {
                            record: parseInt(info.event.id, 10),
                        });
                    },
                    async eventDrop(info) {
                        const id = parseInt(info.event.id, 10);
                        const url = `${rescheduleBaseUrl}/${id}/reschedule`;
                        try {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    starts_at: info.event.start.toISOString(),
                                }),
                            });
                            if (!res.ok) {
                                throw new Error('reschedule failed');
                            }
                        } catch (e) {
                            info.revert();
                        }
                    },
                    eventDidMount(info) {
                        if (info.event.extendedProps.hint) {
                            info.el.setAttribute('title', info.event.extendedProps.hint);
                        }
                    },
                });

                calendar.render();
            }

            document.addEventListener('DOMContentLoaded', boot);
            document.addEventListener('livewire:navigated', boot);
        })(
            @json($calendarEvents),
            @json($calendarLivewireId),
            @json($rescheduleUrl),
        );
    </script>
</x-filament-panels::page>
