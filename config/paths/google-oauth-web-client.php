<?php

declare(strict_types=1);

/**
 * Google OAuth 2 "web" or "installed" client id/secret for Laravel Socialite.
 * Service account JSON cannot be used here — use GOOGLE_OAUTH_CREDENTIALS_PATH (Firebase / GCP OAuth client JSON).
 *
 * Resolution order:
 *  1. GOOGLE_OAUTH_CREDENTIALS_PATH (relative to storage/)
 *  2. Else GOOGLE_SERVICE_ACCOUNT_CREDENTIALS only if that file JSON has top-level "web" or "installed" (same file as Sheets must NOT be used — prefer explicit GOOGLE_OAUTH_CREDENTIALS_PATH).
 *  3. Else empty → config/services.php falls back to GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET from .env.
 */
$relative = env('GOOGLE_OAUTH_CREDENTIALS_PATH');
if (! is_string($relative) || trim($relative) === '') {
    $relative = env('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS');
}

$clientId = null;
$clientSecret = null;

if (is_string($relative) && trim($relative) !== '') {
    $path = storage_path(trim($relative));
    if (is_readable($path)) {
        $raw = file_get_contents($path);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($json)) {
            if (isset($json['web']['client_id'], $json['web']['client_secret'])) {
                $clientId = (string) $json['web']['client_id'];
                $clientSecret = (string) $json['web']['client_secret'];
            } elseif (isset($json['installed']['client_id'], $json['installed']['client_secret'])) {
                $clientId = (string) $json['installed']['client_id'];
                $clientSecret = (string) $json['installed']['client_secret'];
            }
        }
    }
}

return [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
];
