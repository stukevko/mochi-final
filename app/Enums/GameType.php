<?php

namespace App\Enums;

enum GameType: string
{
    case OnePiece = 'one_piece';
    case Pokemon = 'pokemon';
    case Yugioh = 'yugioh';
    case Magic = 'magic';
    case Lorcana = 'lorcana';
    case Digimon = 'digimon';
    case Custom = 'custom';

    /**
     * Standard-Spielarten zuerst, „Eigene Spielart“ zuletzt (Filament & Filter).
     *
     * @return list<self>
     */
    public static function casesForSelect(): array
    {
        $base = array_values(array_filter(
            self::cases(),
            fn (self $g) => $g !== self::Custom,
        ));
        $base[] = self::Custom;

        return $base;
    }

    public function label(): string
    {
        return match ($this) {
            self::OnePiece => 'One Piece',
            self::Pokemon => 'Pokémon',
            self::Yugioh => 'Yu-Gi-Oh!',
            self::Magic => 'Magic: The Gathering',
            self::Lorcana => 'Disney Lorcana',
            self::Digimon => 'Digimon',
            self::Custom => 'Eigene Spielart',
        };
    }

    /**
     * Farben für FullCalendar (dunkles Theme): Pokémon blau, One Piece rot, Magic grün, …
     *
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public function eventCalendarStyle(): array
    {
        return match ($this) {
            self::OnePiece => [
                'backgroundColor' => 'rgba(220, 38, 38, 0.42)',
                'borderColor' => '#f87171',
                'textColor' => '#fef2f2',
            ],
            self::Pokemon => [
                'backgroundColor' => 'rgba(37, 99, 235, 0.42)',
                'borderColor' => '#60a5fa',
                'textColor' => '#eff6ff',
            ],
            self::Yugioh => [
                'backgroundColor' => 'rgba(126, 34, 206, 0.42)',
                'borderColor' => '#c084fc',
                'textColor' => '#faf5ff',
            ],
            self::Magic => [
                'backgroundColor' => 'rgba(22, 163, 74, 0.42)',
                'borderColor' => '#4ade80',
                'textColor' => '#f0fdf4',
            ],
            self::Lorcana => [
                'backgroundColor' => 'rgba(8, 145, 178, 0.4)',
                'borderColor' => '#22d3ee',
                'textColor' => '#ecfeff',
            ],
            self::Digimon => [
                'backgroundColor' => 'rgba(217, 119, 6, 0.4)',
                'borderColor' => '#fbbf24',
                'textColor' => '#fffbeb',
            ],
            self::Custom => [
                'backgroundColor' => 'rgba(148, 163, 184, 0.35)',
                'borderColor' => '#94a3b8',
                'textColor' => '#f8fafc',
            ],
        };
    }

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public static function defaultEventCalendarStyle(): array
    {
        return [
            'backgroundColor' => 'rgba(148, 163, 184, 0.35)',
            'borderColor' => '#94a3b8',
            'textColor' => '#f8fafc',
        ];
    }

    /**
     * Farben für Kalender & Legende (Hintergrund / Rand)
     *
     * @return array{bg: string, border: string}
     */
    public function calendarColors(): array
    {
        return match ($this) {
            self::OnePiece => ['bg' => '#fecdd3', 'border' => '#fb7185'],
            self::Pokemon => ['bg' => '#fde68a', 'border' => '#f59e0b'],
            self::Yugioh => ['bg' => '#e9d5ff', 'border' => '#a855f7'],
            self::Magic => ['bg' => '#bfdbfe', 'border' => '#3b82f6'],
            self::Lorcana => ['bg' => '#cffafe', 'border' => '#06b6d4'],
            self::Digimon => ['bg' => '#bbf7d0', 'border' => '#22c55e'],
            self::Custom => ['bg' => '#e2e8f0', 'border' => '#94a3b8'],
        };
    }

    /** Inline style for pills on dark UI (pastel fill + vivid border). */
    public function badgeStyle(): string
    {
        $c = $this->calendarColors();

        return sprintf(
            'background-color: %s; border: 1px solid %s; color: #0b1220;',
            $c['bg'],
            $c['border'],
        );
    }

    /**
     * Neon accent for shop product cards (inherits category game type).
     *
     * @return array{border: string, shadow: string}
     */
    public function storefrontCardAccent(): array
    {
        return match ($this) {
            self::Pokemon => [
                'border' => '#facc15',
                'shadow' => '0 0 0 1px rgba(250,204,21,0.35), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 38px -12px rgba(250,204,21,0.5), 0 0 64px -22px rgba(59,130,246,0.28)',
            ],
            self::OnePiece => [
                'border' => '#6d28d9',
                'shadow' => '0 0 0 1px rgba(109,40,217,0.32), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 44px -10px rgba(109,40,217,0.55)',
            ],
            self::Lorcana => [
                'border' => '#f59e0b',
                'shadow' => '0 0 0 1px rgba(245,158,11,0.35), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 42px -12px rgba(251,191,36,0.5)',
            ],
            self::Magic => [
                'border' => '#15803d',
                'shadow' => '0 0 0 1px rgba(21,128,61,0.32), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 40px -12px rgba(34,197,94,0.48)',
            ],
            self::Yugioh => [
                'border' => '#ca8a04',
                'shadow' => '0 0 0 1px rgba(202,138,4,0.35), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 46px -10px rgba(234,179,8,0.42)',
            ],
            self::Digimon => [
                'border' => '#f59e0b',
                'shadow' => '0 0 0 1px rgba(245,158,11,0.33), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 40px -12px rgba(245,158,11,0.48)',
            ],
            self::Custom => [
                'border' => '#ff7a1f',
                'shadow' => '0 0 0 1px rgba(255,122,31,0.35), 0 16px 48px -28px rgba(0,0,0,0.85), 0 0 44px -10px rgba(255,148,102,0.52)',
            ],
        };
    }
}
