<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification (VAPID) keys are used to
    | authenticate push messages sent by this server. Generate a key pair
    | with: php artisan webpush:vapid
    |
    | These keys are base64url-encoded.
    |
    */

    'vapid' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
