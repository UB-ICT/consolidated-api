<?php

declare(strict_types=1);

$relative = require __DIR__.'/paths/google-service-account-relative.php';

return [
    /*
    | Path under storage/ to the GCP service account JSON (Sheets, Google Directory API, etc.).
    |
    | Prefer GOOGLE_SERVICE_ACCOUNT_CREDENTIALS in .env. GOOGLE_SHEET_CREDENTIALS and
    | FIREBASE_CREDENTIALS_PATH are fallbacks. FIREBASE_DATABASE_CREDENTIALS_PATH is
    | not used here (Firebase DB only — see config/firebase.php).
    */
    'service_account_credentials_relative' => $relative,
    'service_account_key_path' => storage_path($relative),
];
