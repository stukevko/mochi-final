<?php

/**
 * Deutsche Validierungsmeldungen — Basis: lang/en/validation.php, häufige Regeln übersetzt.
 * Unbenannte Regeln bleiben aus der englischen Datei (lesbar für Admins).
 */
$v = require __DIR__.'/../en/validation.php';

$overrides = [
    'image' => 'Bitte eine Bilddatei verwenden (z. B. JPEG, PNG oder WebP).',
    'mimes' => 'Der Dateityp muss einer der folgenden Typen sein: :values.',
    'mimetypes' => 'Der Dateityp muss einer der folgenden Typen sein: :values.',
    'uploaded' => 'Hochladen fehlgeschlagen. Bitte Dateigröße und Format prüfen oder es später erneut versuchen.',
    'file' => 'Hier muss eine Datei hochgeladen werden.',
    'dimensions' => 'Die Bildabmessungen sind ungültig.',
    'extensions' => 'Die Datei muss eine dieser Endungen haben: :values.',
    'max' => [
        'file' => 'Die Datei darf maximal :max Kilobyte groß sein.',
        'string' => 'Maximal :max Zeichen erlaubt.',
        'array' => 'Es sind höchstens :max Einträge erlaubt.',
        'numeric' => 'Der Wert darf maximal :max sein.',
    ],
    'min' => [
        'file' => 'Die Datei muss mindestens :min Kilobyte groß sein.',
        'string' => 'Mindestens :min Zeichen nötig.',
        'array' => 'Mindestens :min Einträge nötig.',
        'numeric' => 'Der Wert muss mindestens :min sein.',
    ],
    'between' => [
        'file' => 'Die Datei muss zwischen :min und :max Kilobyte groß sein.',
        'string' => 'Zwischen :min und :max Zeichen.',
        'array' => 'Zwischen :min und :max Einträge.',
        'numeric' => 'Der Wert muss zwischen :min und :max liegen.',
    ],
    'url' => 'Bitte eine gültige URL eintragen (inkl. https://).',
    'email' => 'Bitte eine gültige E-Mail-Adresse eintragen.',
    'required' => 'Das Feld „:attribute“ ist erforderlich.',
    'string' => 'Hier wird ein Text erwartet.',
    'numeric' => 'Hier wird eine Zahl erwartet.',
    'integer' => 'Hier wird eine ganze Zahl erwartet.',
    'boolean' => 'Bitte Ja oder Nein wählen.',
    'date' => 'Bitte ein gültiges Datum eintragen.',
    'unique' => 'Dieser Wert ist bereits vergeben.',
    'regex' => 'Das Format ist ungültig.',
    'confirmed' => 'Die Bestätigung stimmt nicht überein.',
];

$merged = array_replace_recursive($v, $overrides);

$merged['attributes'] = array_merge($v['attributes'] ?? [], [
    'title' => 'Titel',
    'starts_at' => 'Startzeitpunkt',
    'image_path' => 'Bild',
    'description' => 'Beschreibung',
    'price' => 'Preis',
    'hero_headline' => 'Hero-Überschrift',
    'hero_visit_store_url' => 'Ladenbesuch-Link',
    'slug' => 'URL-Slug',
    'game_type' => 'Spiel',
    'game_type_other' => 'Name der eigenen Spielart',
    'calendar_color' => 'Kalenderfarbe',
    'status' => 'Status',
    'excerpt' => 'Kurztext',
    'body' => 'Inhalt',
    'published_at' => 'Veröffentlichungsdatum',
    'is_published' => 'Veröffentlicht',
    'post_category_id' => 'Kategorie',
    'type' => 'Typ',
    'shop_cta_url' => 'Shop-Link',
    'hero_logo_path' => 'Hero-Logo',
    'hero_background_path' => 'Hero-Hintergrund',
    'featured_product_image_path' => 'Shop-Renner-Bild',
    'featured_product_title' => 'Shop-Renner-Name',
    'featured_product_description' => 'Shop-Renner-Beschreibung',
    'featured_product_price' => 'Shop-Renner-Preis',
    'featured_product_url' => 'Shop-Renner-Link',
    'instagram_url' => 'Instagram-URL',
    'tiktok_url' => 'TikTok-URL',
    'background_animations' => 'Hintergrund-Animation',
]);

return $merged;
