@props(['type'])

<span
    {{ $attributes->class(
        'inline-flex items-center rounded-lg px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest',
    ) }}
    style="{{ $type->badgeStyle() }}"
>
    {{ $type->label() }}
</span>
