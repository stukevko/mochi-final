<?php

namespace App\Support;

final class BenefitTileIcons
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'bolt' => 'Blitz — Versand',
            'heart' => 'Herz — Community',
            'trophy' => 'Pokal — Turniere / Events',
            'lock' => 'Schloss — Safe Pay',
            'truck' => 'Lieferung',
            'shield' => 'Schutz',
            'sparkles' => 'Highlights',
            'users' => 'Community',
            'credit-card' => 'Bezahlen',
            'shopping-bag' => 'Shopping',
        ];
    }

    public static function defaultForIndex(int $zeroBasedIndex): string
    {
        return match ($zeroBasedIndex % 4) {
            0 => 'bolt',
            1 => 'heart',
            2 => 'trophy',
            default => 'lock',
        };
    }

    /** Kurzliste für Admin-Hilfetexte (Heroicons / interne Keys). */
    public static function adminIconKeysHint(): string
    {
        return implode(', ', array_keys(self::options()));
    }
}
