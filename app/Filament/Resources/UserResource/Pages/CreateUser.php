<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? 'customer';
        $isActive = (bool) ($data['is_active'] ?? true);
        unset($data['role'], $data['is_active']);

        /** @var User $record */
        $record = new User($data);
        $record->forceFill([
            'role' => $role,
            'is_active' => $isActive,
        ]);
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
