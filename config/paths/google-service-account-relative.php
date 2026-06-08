<?php

declare(strict_types=1);

/**
 * Relative path under storage/ to a *service account* JSON (type service_account).
 *
 * Used for Sheets, Google Directory API, and config('google.*') / config('firebase.credentials_path').
 * Does NOT use FIREBASE_DATABASE_CREDENTIALS_PATH — that env is only for the Firebase database
 * (see config/firebase.php database_key_path).
 */
$candidates = [
    'GOOGLE_SERVICE_ACCOUNT_CREDENTIALS',
    'GOOGLE_SHEET_CREDENTIALS',
    'FIREBASE_CREDENTIALS_PATH',
];

foreach ($candidates as $key) {
    $val = env($key);
    if (! is_string($val) || trim($val) === '') {
        continue;
    }
    $relative = trim($val);
    $path = storage_path($relative);
    if (! is_readable($path)) {
        continue;
    }
    $raw = file_get_contents($path);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($json) && ($json['type'] ?? null) === 'service_account') {
        return $relative;
    }
}

return 'app/google-service-account.json';
