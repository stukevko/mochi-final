@props(['game' => null, 'for' => null, 'muted' => false])

@php
    if ($for instanceof \App\Models\Event || $for instanceof \App\Models\Post) {
        $label = $for->gameTypeLabel();
        $style = $for->gameTypeBadgeStyle();
    } elseif ($game instanceof \App\Enums\GameType) {
        $label = $game->label();
        $style = $game->badgeStyle();
    } else {
        $label = '';
        $style = 'background-color: #e2e8f0; border: 1px solid #94a3b8; color: #0b1220;';
    }
@endphp

<span
    {{ $attributes->class(
        $muted
            ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide ring-1 ring-white/12'
            : 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide shadow-[0_0_12px_-4px_rgba(255,122,31,0.45)]',
    ) }}
    style="{{ $style }}"
>
    {{ $label }}
</span>
