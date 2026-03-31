@php
    /** @var \App\Models\Post $post */
    $thumbClass = $class ?? '';
    $coverUrl = null;
    if ($post->cover_image_path) {
        $p = $post->cover_image_path;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($p)) {
            $coverUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($p);
        } elseif (is_file(public_path($p))) {
            $coverUrl = asset($p);
        }
    }
@endphp

@if ($coverUrl)
    <div
        class="relative aspect-[5/3] w-full overflow-hidden rounded-3xl ring-1 ring-white/10 {{ $thumbClass }}"
    >
        <img
            src="{{ $coverUrl }}"
            alt=""
            class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.07]"
            loading="lazy"
            decoding="async"
        >
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#05080d]/90 via-[#05080d]/20 to-transparent"
        ></div>
    </div>
@else
    @php
        if ($post->game_type) {
            $c = $post->gameTypeCalendarColors();
            $g1 = $c['bg'];
            $g2 = $c['border'];
        } else {
            $g1 = $post->type === \App\Enums\PostType::News ? 'rgba(255,122,31,0.35)' : 'rgba(96,165,250,0.28)';
            $g2 = $post->type === \App\Enums\PostType::News ? 'rgba(180,60,10,0.5)' : 'rgba(30,80,160,0.45)';
        }
        $seed = crc32((string) $post->getKey() . $post->getAttribute('slug'));
        $rot = ($seed % 24) - 12;
    @endphp

    <div
        class="relative aspect-[5/3] w-full overflow-hidden rounded-3xl ring-1 ring-white/10 {{ $thumbClass }}"
        style="background: linear-gradient({{ 128 + ($seed % 40) }}deg, {{ $g1 }} 0%, rgba(11,15,22,0.96) 55%, {{ $g2 }} 120%);"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.22]"
            style="background-image: repeating-linear-gradient(
            -12deg,
            transparent,
            transparent 6px,
            rgba(255,255,255,0.04) 6px,
            rgba(255,255,255,0.04) 7px
        ), repeating-linear-gradient(
            {{ $rot }}deg,
            transparent,
            transparent 14px,
            rgba(255,122,31,0.06) 14px,
            rgba(255,122,31,0.06) 15px
        );"
        ></div>
        <div
            class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full opacity-40 blur-2xl"
            style="background: radial-gradient(circle, {{ $g2 }} 0%, transparent 70%);"
        ></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <span
                class="font-display text-4xl font-black tabular-nums text-white/90 drop-shadow-[0_0_24px_rgba(0,0,0,0.65)]"
                aria-hidden="true"
            >
                {{ strtoupper(\Illuminate\Support\Str::substr($post->title, 0, 1)) }}
            </span>
        </div>
    </div>
@endif
