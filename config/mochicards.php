<?php

return [
    'instagram_url' => env('MOCHI_INSTAGRAM_URL', 'https://www.instagram.com/'),
    'tiktok_url' => env('MOCHI_TIKTOK_URL', ''),
    'site_name' => env('MOCHI_SITE_NAME', 'Mochi Cards'),
    'default_shop_url' => env('MOCHI_SHOP_URL', 'https://example.com'),
    /**
     * Absoluter Fallback für „Besuch uns im Laden“, wenn das Admin-Feld leer ist.
     * (Google Maps — Suche „Mochi Cards Speyer“)
     */
    'default_visit_store_url' => env(
        'MOCHI_VISIT_STORE_URL',
        'https://www.google.com/maps/search/?api=1&query=Mochi+Cards+Speyer+Germany',
    ),

    /** Anwendungs-Cache für GET /events/feed (Sekunden) */
    'events_feed_cache_seconds' => (int) env('MOCHI_EVENTS_FEED_CACHE_SECONDS', 600),

    /** Browser-Cache (Cache-Control max-age) für /events/feed */
    'events_feed_http_max_age' => (int) env('MOCHI_EVENTS_FEED_HTTP_MAX_AGE', 300),

    /** Standard-Headline (Hero), wenn im Admin leer — lokal für Speyer */
    'home_hero_headline_default' => env(
        'MOCHI_HERO_HEADLINE',
        'DEIN WOHNZIMMER FÜR TCG IN SPEYER',
    ),

    /**
     * Standard-Shop-Highlight (Titel & Langtext), wenn Admin leer bzw. ohne Beschreibung.
     * Optional per .env überschreiben (einzeilig; für längere Texte Admin-Feld nutzen).
     */
    'home_featured_default_title' => env(
        'MOCHI_FEATURED_TITLE',
        'Shop-Highlight: Kevko\'s Community Token - Limitierte Edition',
    ),

    'home_featured_default_description' => env(
        'MOCHI_FEATURED_DESCRIPTION',
        'Dies ist mehr als nur eine Karte. Es ist Kevko\'s persönliche Hommage an euch, die Speyerer TCG-Szene. Jedes Exemplar wurde von ihm zum Launch des Ladens hand-illustriert und signiert. Ein echtes Stück „Mochi-Wohnzimmer“ für deine Sammlung.',
    ),

    /** News-Vorschau (Fallback), wenn keine Beiträge online — „Always-Open“-Vibe */
    'home_news_fallback_title' => env(
        'MOCHI_NEWS_FALLBACK_TITLE',
        'Wir haben aufgerüstet: Mehr Platz für eure Matches!',
    ),

    'home_news_fallback_body' => env(
        'MOCHI_NEWS_FALLBACK_BODY',
        'Dank Kevko\'s unermüdlichem Einsatz haben wir jetzt noch mehr Platz für Tische und eine neue Kaffeeküche. Kommt vorbei und checkt es aus!',
    ),

    /**
     * Kontaktseite — Öffnungszeiten & Einleitung (Adresse kommt bevorzugt aus Setting shop_address).
     */
    'contact_intro' => env(
        'MOCHI_CONTACT_INTRO',
        'Schreib uns — zu Bestellungen, Events, Ankauf oder allem rund ums Hobby. Wir antworten per E-Mail so schnell wir können.',
    ),

    'contact_opening_hours' => <<<'TEXT'
Mo–Fr: 12:00 – 20:00 Uhr
Sa: 11:00 – 18:00 Uhr
So: geschlossen
TEXT,

    /** iframe src — ohne API-Key: klassisches output=embed (im Admin über .env überschreibbar). */
    'contact_maps_embed_url' => env(
        'MOCHI_MAPS_EMBED_URL',
        'https://maps.google.com/maps?q=Maximilianstra%C3%9Fe+42%2C+67346+Speyer&hl=de&z=16&output=embed',
    ),
];
