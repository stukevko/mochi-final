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

    'order_notification_email' => env('SHOP_ORDER_NOTIFICATION_EMAIL', 'kontakt@kevko.studio'),

];
