<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $role = $data['role'] ?? $record->role;
        $isActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : (bool) $record->is_active;
        unset($data['role'], $data['is_active']);

        $record->update($data);
        $record->forceFill([
            'role' => $role,
            'is_active' => $isActive,
        ])->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Prevent deleting last admin (logic already in Resource)
                    if ($this->record->role === 'admin' && \App\Models\User::where('role', 'admin')->count() <= 1) {
                        \Filament\Notifications\Notification::make()
                            ->title('Letzter Admin kann nicht gelöscht werden')
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
