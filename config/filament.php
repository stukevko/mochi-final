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

];
