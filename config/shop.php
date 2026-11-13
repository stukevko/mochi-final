<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Benachrichtigung bei neuer Bestellung (Betreiber)
    |--------------------------------------------------------------------------
    |
    | Erhält eine Kopie (BCC) der Bestellbestätigung an die Kundenadresse.
    | Leer lassen, um keine Shop-Kopie zu senden.
    |
    */

    'order_notification_email' => env('SHOP_ORDER_NOTIFICATION_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Betrieb auf normalem Webspace (ohne Cronjob / ohne SSH)
    |--------------------------------------------------------------------------
    |
    | WEB_CRON=true: Die wiederkehrenden Aufgaben (Zahlungsabgleich, Kassenabgleich,
    | Datenbank-Updates nach einem neuen Upload) laufen automatisch im Hintergrund mit,
    | sobald jemand die Seite aufruft. Der Installer schaltet das ein.
    |
    | CRON_TOKEN: Für Hoster mit „Cronjob per URL“ → https://domain/cron/<token>
    |
    */

    'web_cron' => (bool) env('WEB_CRON', false),

    'cron_token' => env('CRON_TOKEN'),

];
