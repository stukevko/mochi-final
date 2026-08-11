<?php

namespace App\Enums;

enum EventRecurrence: string
{
    case None = 'none';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Keine Wiederholung',
            self::Weekly => 'Wöchentlich',
            self::Biweekly => 'Alle 2 Wochen',
            self::Monthly => 'Monatlich',
        };
    }

    public function isRepeating(): bool
    {
        return $this !== self::None;
    }
}
