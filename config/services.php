<?php

declare(strict_types=1);

$googleOAuth = require __DIR__.'/paths/google-oauth-web-client.php';
$googleClientId = $googleOAuth['client_id'] ?? env('GOOGLE_CLIENT_ID');
$googleClientSecret = $googleOAuth['client_secret'] ?? env('GOOGLE_CLIENT_SECRET');

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have a
    | conventional location to find the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect' => env('GOOGLE_REDIRECT', 'http://localhost:3031/auth/google/callback'),
        'redirect_uri' => env('GOOGLE_REDIRECT', 'http://localhost:3031/auth/google/callback'),
        'domain' => env('GOOGLE_DOMAIN', 'ub.edu.bz'),
    ],

    'google_public_safety' => [
        'client_id' => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect' => env('GOOGLE_PUBLIC_SAFETY_REDIRECT_URI', 'http://localhost:3031/auth/google/public-safety-callback'),
        'redirect_uri' => env('GOOGLE_PUBLIC_SAFETY_REDIRECT_URI', 'http://localhost:3031/auth/google/public-safety-callback'),
        'domain' => env('GOOGLE_DOMAIN', 'ub.edu.bz'),
    ],

    'google_annual_report' => [
        'client_id' => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect' => env('GOOGLE_ANNUAL_REPORT_REDIRECT_URI', 'http://localhost:3031/auth/google/annual-report-callback'),
        'redirect_uri' => env('GOOGLE_ANNUAL_REPORT_REDIRECT_URI', 'http://localhost:3031/auth/google/annual-report-callback'),
        'domain' => env('GOOGLE_DOMAIN', 'ub.edu.bz'),
    ],
];
