{{-- App-Shell-Meta: installierbar, Vollbild auf iOS/Android, korrekte Statusleisten-Farbe. --}}
<meta name="theme-color" content="#040712">
<meta name="color-scheme" content="dark">
<meta name="format-detection" content="telephone=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($shopDisplayName ?? config('app.name'), 14, '') }}">
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
{{-- Sofort-Navigation (Chromium): Seiten beim Antippen/Hover vorab laden. Nie Admin/Warenkorb/Checkout. --}}
<script type="speculationrules">
{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/admin*","/cart*","/checkout*","/livewire*","/webhooks/*","/api/*"]}},{"not":{"selector_matches":"[rel~=nofollow],[target=_blank]"}}]},"eagerness":"moderate"}]}
</script>
