<?php

namespace App\Filament\Pages;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
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
                unset($data['allow_slug_edit']);
                $record->update($data);
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
                    ],
                    'classNames' => $isArchived ? ['mochi-fc-event-archived'] : [],
                ];
            })->values()->all(),
            'gameLegend' => $legend,
            'calendarLivewireId' => $this->getId(),
            'rescheduleUrl' => url('/admin-calendar/events'),
        ];
    }
}
