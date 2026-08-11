<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\EventRecurrence;
use App\Filament\Resources\Events\EventResource;
use App\Services\Events\RecurringEventService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $recurrence = EventRecurrence::tryFrom((string) ($data['recurrence'] ?? EventRecurrence::None->value))
            ?? EventRecurrence::None;

        if (! $recurrence->isRepeating()) {
            $data['recurrence'] = EventRecurrence::None->value;
            $data['recurrence_until'] = null;
        }

        $data['parent_event_id'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $created = app(RecurringEventService::class)->expandSeries($this->record);

        if ($created === []) {
            return;
        }

        Notification::make()
            ->title('Serie angelegt')
            ->body((count($created) + 1).' Termine im Kalender (inkl. erstem Termin).')
            ->success()
            ->send();
    }
}
