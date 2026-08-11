<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\EventRecurrence;
use App\Filament\Resources\Events\EventResource;
use App\Services\Events\RecurringEventService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected ?bool $shouldSyncSeries = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription(fn (): ?string => $this->record->isSeriesRoot()
                    ? 'Löscht diesen Serien-Start und alle zugehörigen Folgetermine.'
                    : null),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->shouldSyncSeries = (bool) ($this->data['sync_series_content'] ?? false);

        // Series children must not redefine recurrence / parent.
        if ($this->record->isSeriesChild()) {
            unset($data['recurrence'], $data['recurrence_until'], $data['parent_event_id']);

            return $data;
        }

        $recurrence = EventRecurrence::tryFrom((string) ($data['recurrence'] ?? EventRecurrence::None->value))
            ?? EventRecurrence::None;

        // Editing an existing series: keep stored recurrence metadata, do not re-expand.
        if ($this->record->isSeriesRoot()) {
            $data['recurrence'] = $this->record->recurrence?->value ?? EventRecurrence::None->value;
            $data['recurrence_until'] = optional($this->record->recurrence_until)?->toDateString();
        } elseif (! $recurrence->isRepeating()) {
            $data['recurrence'] = EventRecurrence::None->value;
            $data['recurrence_until'] = null;
        }

        unset($data['sync_series_content'], $data['allow_slug_edit']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        if ($this->shouldSyncSeries && $record->isSeriesRoot()) {
            $updated = app(RecurringEventService::class)->syncFutureChildren($record);

            if ($updated > 0) {
                Notification::make()
                    ->title('Serie aktualisiert')
                    ->body($updated.' zukünftige Termine übernommen.')
                    ->success()
                    ->send();
            }
        }

        // Einzeltermin nachträglich zur Serie machen (noch ohne Kinder).
        if ($record->parent_event_id === null
            && $record->recurrence?->isRepeating()
            && $record->children()->doesntExist()) {
            $created = app(RecurringEventService::class)->expandSeries($record);

            if ($created !== []) {
                Notification::make()
                    ->title('Serie angelegt')
                    ->body((count($created) + 1).' Termine im Kalender (inkl. diesem Termin).')
                    ->success()
                    ->send();
            }
        }
    }
}
