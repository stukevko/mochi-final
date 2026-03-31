<?php

namespace App\Enums;

enum ContactSubject: string
{
    case Order = 'order';
    case Event = 'event';
    case Buylist = 'buylist';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Bestellung',
            self::Event => 'Event-Frage',
            self::Buylist => 'Ankauf',
            self::Other => 'Sonstiges',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function optionsForForm(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $out;
    }
}
