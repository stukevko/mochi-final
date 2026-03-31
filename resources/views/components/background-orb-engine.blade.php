@props([
    'enabled' => true,
])

@if ($enabled)
    {{-- Nur weiche Farblichtung (Orange/Blau) — ohne Pink, wenige Orbs = weniger „Glow-Suppe“ --}}
    <div class="mochi-orb-engine" aria-hidden="true">
        <div class="mochi-orb-anchor mochi-orb-anchor--1" data-mochi-parallax="0.011">
            <div class="mochi-orb-drift mochi-orb-drift--1">
                <div class="mochi-orb-bloom mochi-orb-bloom--orange"></div>
            </div>
        </div>
        <div class="mochi-orb-anchor mochi-orb-anchor--2" data-mochi-parallax="-0.009">
            <div class="mochi-orb-drift mochi-orb-drift--2">
                <div class="mochi-orb-bloom mochi-orb-bloom--sky"></div>
            </div>
        </div>
        <div class="mochi-orb-anchor mochi-orb-anchor--3 hidden sm:block" data-mochi-parallax="0.007">
            <div class="mochi-orb-drift mochi-orb-drift--3">
                <div class="mochi-orb-bloom mochi-orb-bloom--coral-soft"></div>
            </div>
        </div>
        <div class="mochi-orb-anchor mochi-orb-anchor--4 hidden md:block" data-mochi-parallax="-0.01">
            <div class="mochi-orb-drift mochi-orb-drift--4">
                <div class="mochi-orb-bloom mochi-orb-bloom--azure"></div>
            </div>
        </div>
    </div>
@endif
