@php
    $bgAnim = (bool) ($backgroundAnimationsEnabled ?? true);
@endphp
<div
    id="mochi-parallax-root"
    class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
    aria-hidden="true"
    data-mochi-background-animations="{{ $bgAnim ? '1' : '0' }}"
>
    @if ($bgAnim)
        <x-background-orb-engine :enabled="true" />
        <div class="mochi-pokeball-anchor mochi-pokeball-anchor--1" data-mochi-parallax="0.026">
            <div class="mochi-pokeball-drift mochi-pokeball-drift--a">
                <div class="mochi-pokeball-blur">
                    <div class="mochi-pokeball-face mochi-orb-ball"></div>
                </div>
            </div>
        </div>
        <div class="mochi-pokeball-anchor mochi-pokeball-anchor--2" data-mochi-parallax="-0.017">
            <div class="mochi-pokeball-drift mochi-pokeball-drift--b">
                <div class="mochi-pokeball-blur">
                    <div class="mochi-pokeball-face mochi-orb-ball"></div>
                </div>
            </div>
        </div>
        <div class="mochi-pokeball-anchor mochi-pokeball-anchor--3 hidden md:block" data-mochi-parallax="0.02">
            <div class="mochi-pokeball-drift mochi-pokeball-drift--c">
                <div class="mochi-pokeball-blur mochi-pokeball-blur--soft">
                    <div class="mochi-pokeball-face mochi-orb-ball"></div>
                </div>
            </div>
        </div>
        <div class="mochi-pokeball-anchor mochi-pokeball-anchor--4 hidden md:block" data-mochi-parallax="-0.011">
            <div class="mochi-pokeball-drift mochi-pokeball-drift--d">
                <div class="mochi-pokeball-blur">
                    <div class="mochi-pokeball-face mochi-orb-ball"></div>
                </div>
            </div>
        </div>
    @endif
</div>
