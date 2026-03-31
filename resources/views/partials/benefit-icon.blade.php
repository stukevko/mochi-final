@props([
    'name' => 'bolt',
    'compact' => false,
])

@php
    $class = $compact
        ? 'h-10 w-10 sm:h-11 sm:w-11 shrink-0 text-mochi-coral'
        : 'h-[4.5rem] w-[4.5rem] sm:h-24 sm:w-24 shrink-0 text-mochi-coral drop-shadow-[0_0_28px_rgba(255,122,31,0.35)]';
@endphp

@switch($name)
    @case('heart')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path
                d="M12 21s-6.5-4.35-9-8.5C.5 9.85 2.22 6.42 5.5 5.05 7.87 4.17 10.5 5.3 12 7.2 13.5 5.3 16.13 4.17 18.5 5.05 21.78 6.42 23.5 9.85 21 12.5 18.5 16.65 12 21 12 21z"
                stroke-linejoin="round"
            />
        </svg>
        @break
    @case('trophy')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M8 21h8M12 17v4" stroke-linecap="round" />
            <path d="M7 4h10v3a5 5 0 0 1-10 0V4z" stroke-linejoin="round" />
            <path d="M7 7H5a2 2 0 0 0-2 2v1a3 3 0 0 003 3M17 7h2a2 2 0 0 1 2 2v1a3 3 0 0 1-3 3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break
    @case('lock')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke-linecap="round" />
            <rect x="4" y="11" width="16" height="11" rx="2" stroke-linejoin="round" />
        </svg>
        @break
    @case('truck')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M14 18V6H4v12h10z" stroke-linejoin="round" />
            <path d="M14 9h4l3 4v5h-4" stroke-linejoin="round" />
            <circle cx="7" cy="18" r="2" fill="currentColor" stroke="none" />
            <circle cx="17" cy="18" r="2" fill="currentColor" stroke="none" />
        </svg>
        @break
    @case('shield')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M12 3l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V7l8-4z" stroke-linejoin="round" />
        </svg>
        @break
    @case('sparkles')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M12 2l1.8 5.5h5.8l-4.7 3.4 1.8 5.5-4.7-3.4-4.7 3.4 1.8-5.5L4.4 7.5h5.8L12 2z" stroke-linejoin="round" />
        </svg>
        @break
    @case('users')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" />
        </svg>
        @break
    @case('credit-card')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <rect x="2" y="5" width="20" height="14" rx="2" stroke-linejoin="round" />
            <path d="M2 10h20" />
        </svg>
        @break
    @case('shopping-bag')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M6 7h15l-1 13H7L6 7z" stroke-linejoin="round" />
            <path d="M9 7V6a3 3 0 0 1 6 0v1" stroke-linecap="round" />
        </svg>
        @break
    @default
        {{-- bolt --}}
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M13 2L4 14h7l-1 8 10-14h-7l0-6z" stroke-linejoin="round" />
        </svg>
@endswitch

