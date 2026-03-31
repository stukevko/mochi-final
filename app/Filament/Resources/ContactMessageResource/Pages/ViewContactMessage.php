<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $message = $this->getRecord();
        if ($message->status === ContactMessageStatus::New) {
            $message->update(['status' => ContactMessageStatus::Read]);
            $this->record = $message->fresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
