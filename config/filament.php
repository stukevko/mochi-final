<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin-Session (Inaktivität, Minuten)
    |--------------------------------------------------------------------------
    |
    | Gilt für Request in Panels, die ConfigureFilamentSession einbinden.
    | Kann mit FILAMENT_SESSION_LIFETIME in der .env überschrieben werden.
    |
    */

    'session_lifetime' => (int) env('FILAMENT_SESSION_LIFETIME', 120),

    /*
    |--------------------------------------------------------------------------
    | Passkey-MFA erzwingen
    |--------------------------------------------------------------------------
    |
    | Muss über die Config gelesen werden (nicht env() im Provider), sonst ist
    | der Wert nach `php artisan config:cache` in Production immer false.
    |
    */

    'require_2fa' => (bool) env('FILAMENT_REQUIRE_2FA', false),

];
