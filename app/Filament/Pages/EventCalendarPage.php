<?php

namespace App\Filament\Pages;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Models\Event;
use App\Services\Events\RecurringEventService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EventCalendarPage extends Page
{
    protected static ?string $slug = 'event-calendar';

    protected static string|UnitEnum|null $navigationGroup = '📰 Inhalte';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Kalender-Ansicht';

    protected static ?string $title = 'Event-Kalender';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected string $view = 'filament.pages.event-calendar-page';

    public function editCalendarEventAction(): Action
    {
        return Action::make('editCalendarEvent')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalHeading('Event bearbeiten')
            ->modalSubmitActionLabel('Speichern')
            ->closeModalByClickingAway(false)
            ->record(fn (array $arguments): Event => Event::findOrFail($arguments['record']))
            ->fillForm(fn (Action $action): array => $action->getRecord()->attributesToArray())
            ->schema(fn (Schema $schema): Schema => EventForm::configure(
                $schema->model(Event::class)->operation('edit')
            ))
            ->action(function (array $data, Event $record): void {
                $syncSeries = (bool) ($data['sync_series_content'] ?? false);
                unset($data['allow_slug_edit'], $data['sync_series_content']);

                if ($record->isSeriesChild()) {
                    unset($data['recurrence'], $data['recurrence_until'], $data['parent_event_id']);
                }

                if ($record->isSeriesRoot()) {
                    $data['recurrence'] = $record->recurrence?->value;
                    $data['recurrence_until'] = optional($record->recurrence_until)?->toDateString();
                }

                $record->update($data);
                $record = $record->fresh();

                if ($syncSeries && $record->isSeriesRoot()) {
                    $updated = app(RecurringEventService::class)->syncFutureChildren($record);
                    if ($updated > 0) {
                        Notification::make()
                            ->title('Serie aktualisiert')
                            ->body($updated.' zukünftige Termine übernommen.')
                            ->success()
                            ->send();
                    }
                }

                if ($record->parent_event_id === null
                    && $record->recurrence?->isRepeating()
                    && $record->children()->doesntExist()) {
                    $created = app(RecurringEventService::class)->expandSeries($record);
                    if ($created !== []) {
                        Notification::make()
                            ->title('Serie angelegt')
                            ->body((count($created) + 1).' Termine im Kalender.')
                            ->success()
                            ->send();
                    }
                }
            })
            ->successNotificationTitle('Event gespeichert.');
    }

    protected function getViewData(): array
    {
        $events = Event::query()
            ->whereBetween('starts_at', [now()->subMonths(3), now()->addMonths(12)])
            ->orderBy('starts_at')
            ->get();

        $legend = collect(GameType::casesForSelect())->map(fn (GameType $g) => [
            'label' => $g->label(),
            'badgeStyle' => $g->badgeStyle(),
        ])->values()->all();

        return [
            'calendarEvents' => $events->map(function (Event $event) {
                $isArchived = $event->status === EventStatus::Archived;
                $colors = $isArchived
                    ? [
                        'backgroundColor' => 'rgba(148, 163, 184, 0.25)',
                        'borderColor' => '#64748b',
                        'textColor' => '#e2e8f0',
                    ]
                    : $event->calendarFeedColors();

                return [
                    'id' => (string) $event->id,
                    'title' => $event->title,
                    'start' => $event->starts_at->toIso8601String(),
                    'allDay' => false,
                    'backgroundColor' => $colors['backgroundColor'],
                    'borderColor' => $colors['borderColor'],
                    'textColor' => $colors['textColor'],
                    'editable' => true,
                    'durationEditable' => false,
                    'extendedProps' => [
                        'hint' => $event->gameTypeLabel(),
                        'archived' => $isArchived,
                        'series' => $event->isSeriesRoot() || $event->isSeriesChild(),
                    ],
                    'classNames' => array_values(array_filter([
                        $isArchived ? 'mochi-fc-event-archived' : null,
                        ($event->isSeriesRoot() || $event->isSeriesChild()) ? 'mochi-fc-event-series' : null,
                    ])),
                ];
            })->values()->all(),
            'gameLegend' => $legend,
            'calendarLivewireId' => $this->getId(),
            'rescheduleUrl' => url('/admin-calendar/events'),
        ];
    }
}
