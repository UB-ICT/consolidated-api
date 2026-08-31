<?php

declare(strict_types=1);

/**
 * Firebase: default service account path for non-database Google APIs (from
 * google-service-account-relative.php — never FIREBASE_DATABASE_CREDENTIALS_PATH).
 * Plus dedicated service account + project for the Firebase *database* only.
 */
$relative = require __DIR__.'/paths/google-service-account-relative.php';
$defaultCredentialsPath = storage_path($relative);

$dbRelative = env('FIREBASE_DATABASE_CREDENTIALS_PATH');
if (! is_string($dbRelative) || trim($dbRelative) === '') {
    $dbRelative = env('FIRESTORE_CREDENTIALS_PATH');
}
$databaseKeyPath = (is_string($dbRelative) && trim($dbRelative) !== '')
    ? storage_path(trim($dbRelative))
    : $defaultCredentialsPath;

$projectId = env('FIREBASE_PROJECT_ID');
if (! is_string($projectId) || trim($projectId) === '') {
    $projectId = env('FIRESTORE_PROJECT_ID');
}
if (! is_string($projectId) || trim($projectId) === '') {
    $projectId = env('GOOGLE_CLOUD_PROJECT_ID');
}

return [
    /** @deprecated use credentials_path */
    'firebase' => $defaultCredentialsPath,

    /** Default service account path (Sheets, Directory API, etc.) */
    'credentials_path' => $defaultCredentialsPath,

    /**
     * Firebase database: absolute path to service account JSON (same GCP project as the DB).
     * Not the web client `firebase-credentials.json` — use Project settings → Service accounts → key.
     */
    'database_key_path' => $databaseKeyPath,

    /** Firebase / GCP project ID for the database (e.g. ubapps-450f8). */
    'database_project_id' => is_string($projectId) ? trim($projectId) : null,

    /** Icon shown in browser push notifications. */
    'web_push_icon' => env('FIREBASE_WEB_PUSH_ICON', '/vite.svg'),
];
