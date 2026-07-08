@if (! request()->is('admin*'))
    @php
        $consentLayout = ($consentLayout ?? null) === 'shop' ? 'shop' : 'storefront';
        $consentConfig = \App\Support\CookieConsentConfig::forLayout($consentLayout);
    @endphp
    <script id="mochi-consent-config" type="application/json">
        @json($consentConfig)
    </script>
@endif
