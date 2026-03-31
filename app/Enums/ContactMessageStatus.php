<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::Read => 'Gelesen',
            self::InProgress => 'In Bearbeitung',
            self::Done => 'Erledigt',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Done;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function optionsForSelect(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $out;
    }
}
