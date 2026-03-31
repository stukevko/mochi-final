<?php

namespace App\Models\Concerns;

use App\Enums\GameType;
use Illuminate\Database\Eloquent\Model;

trait HasGameTypeDisplay
{
    public static function bootHasGameTypeDisplay(): void
    {
        static::saving(function (Model $model): void {
            $type = $model->getAttribute('game_type');
            if (! $type instanceof GameType || $type !== GameType::Custom) {
                $model->setAttribute('game_type_other', null);
            }
        });
    }

    public function gameTypeLabel(): string
    {
        if ($this->game_type === GameType::Custom) {
            $other = trim((string) $this->getAttribute('game_type_other'));

            return $other !== '' ? $other : GameType::Custom->label();
        }

        return $this->game_type?->label() ?? '';
    }

    /**
     * @return array{bg: string, border: string}
     */
    public function gameTypeCalendarColors(): array
    {
        return $this->game_type?->calendarColors()
            ?? ['bg' => '#e2e8f0', 'border' => '#94a3b8'];
    }

    public function gameTypeBadgeStyle(): string
    {
        $c = $this->gameTypeCalendarColors();

        return sprintf(
            'background-color: %s; border: 1px solid %s; color: #0b1220;',
            $c['bg'],
            $c['border'],
        );
    }
}
