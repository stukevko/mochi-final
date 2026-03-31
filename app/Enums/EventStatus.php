<?php

namespace App\Enums;

enum EventStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Archived => 'Archiv',
        };
    }
}
