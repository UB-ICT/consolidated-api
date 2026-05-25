<?php

// app/Services/FirestoreService.php
// Purpose: Firebase database access via the Firestore API (google-cloud-firestore).

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FirestoreService
{
    protected static $firestore;

    private final function __construct()
    {
    }


    /**
     * @throws InvalidArgumentException when credentials JSON is missing or not a Google service account key
     */
    protected static function assertFirebaseDatabaseKeyFile(string $absolutePath): void
    {
        if (! is_readable($absolutePath)) {
            throw new InvalidArgumentException(
                "Firebase database credentials file is missing or not readable: {$absolutePath}. "
                .'Set FIREBASE_DATABASE_CREDENTIALS_PATH to a *service account* JSON under storage/ (e.g. app/ubapps-firestore-service-account.json), '
                .'or rely on GOOGLE_SERVICE_ACCOUNT_CREDENTIALS when the database is in the same GCP project. '
                .'(Legacy: FIRESTORE_CREDENTIALS_PATH is still read.)'
            );
        }

        $raw = file_get_contents($absolutePath);
        if ($raw === false || trim($raw) === '') {
            throw new InvalidArgumentException("Firebase database credentials file is empty: {$absolutePath}");
        }

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            throw new InvalidArgumentException(
                "Firebase database credentials file is not valid JSON: {$absolutePath}"
            );
        }

        if (! array_key_exists('type', $json)) {
            $hint = isset($json['web'])
                ? ' This file looks like a Firebase *web* / OAuth client (top-level "web" key). The server needs a *service account* JSON from Firebase / IAM (top-level "type": "service_account").'
                : '';

            throw new InvalidArgumentException(
                'Google credentials JSON is missing the required "type" field.'
                .$hint
                ." Check FIREBASE_DATABASE_CREDENTIALS_PATH or GOOGLE_SERVICE_ACCOUNT_CREDENTIALS (resolved to {$absolutePath})."
            );
        }

        if (($json['type'] ?? '') !== 'service_account') {
            throw new InvalidArgumentException(
                'Firebase database access expects a service account key (type = service_account). Got type "'
                .(string) ($json['type'] ?? '')
                ."\" in {$absolutePath}"
            );
        }

        $privateKey = $json['private_key'] ?? null;
        if (! is_string($privateKey) || trim($privateKey) === '') {
            throw new InvalidArgumentException(
                "Service account JSON at {$absolutePath} has no usable private_key. Regenerate the key in Firebase / Google Cloud Console."
            );
        }
        if (extension_loaded('openssl') && openssl_pkey_get_private($privateKey) === false) {
            throw new InvalidArgumentException(
                "private_key in {$absolutePath} is not valid PEM (corrupted file, bad line endings, or truncated). "
                .'Regenerate a new JSON key and redeploy; do not paste the key through tools that strip newlines.'
            );
        }

        $keyProject = isset($json['project_id']) ? (string) $json['project_id'] : '';
        $envProject = config('firebase.database_project_id');
        $envProject = is_string($envProject) ? trim($envProject) : '';
        if ($keyProject !== '' && $envProject !== '' && $keyProject !== $envProject) {
            throw new InvalidArgumentException(
                "Firebase project (FIREBASE_PROJECT_ID / GOOGLE_CLOUD_PROJECT_ID = {$envProject}) does not match project_id in the key file ({$keyProject}). "
                .'Mismatch causes PERMISSION_DENIED. Use a service account JSON from the same GCP project as your Firebase database.'
            );
        }
    }

    protected static function initializeFirestore()
    {
        if (is_null(self::$firestore)) {

            $keyPath = config('firebase.database_key_path');
            self::assertFirebaseDatabaseKeyFile($keyPath);

            $projectId = config('firebase.database_project_id');
            if (! is_string($projectId) || trim($projectId) === '') {
                throw new InvalidArgumentException(
                    'Firebase project ID is not set. Set FIREBASE_PROJECT_ID or GOOGLE_CLOUD_PROJECT_ID in .env '
                    .'(e.g. ubapps-450f8). Legacy: FIRESTORE_PROJECT_ID is still read.'
                );
            }

            self::$firestore = new FirestoreClient([
                'projectId' => trim($projectId),
                'keyFilePath' => $keyPath,
            ]);
        }
    }

    public static function firestore()
    {
        self::initializeFirestore();
        return self::$firestore;
    }

    public static function getCollection(string $collectionName): array
    {
        try {
            self::initializeFirestore();
            $collection = self::$firestore->collection($collectionName);
            $documents = $collection->documents();

            $result = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id(); // Add the document ID
                    $result[] = $data;
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error retrieving collection from Firebase database', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public static function getCollectionWhere(string $collection, string $field, string $operator, $value): array
    {
        self::initializeFirestore();

        try {
            $collectionRef = self::$firestore->collection($collection);
            $query = $collectionRef->where($field, $operator, $value);
            $documents = $query->documents();

            $results = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id(); // include document ID
                    $results[] = $data;
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Firebase database getCollectionWhere error: '.$e->getMessage());
            return [];
        }
    }

    public static function syncDocumentAndGetRef(string $collection, array $data): DocumentReference
    {
        self::initializeFirestore();

        $collectionRef = self::$firestore->collection($collection);
        $documentRef = $collectionRef->add($data);

        return $documentRef;
    }

    public static function getDocument(string $collection, string $documentId): ?array
    {
        self::initializeFirestore();

        $documentRef = self::$firestore->collection($collection)->document($documentId);
        $snapshot = $documentRef->snapshot();

        if ($snapshot->exists()) {
            return $snapshot->data();
        }

        return null;
    }

    public static function updateDocument(string $collection, string $documentId, array $data): bool
    {
        self::initializeFirestore();

        $documentRef = self::$firestore->collection($collection)->document($documentId);
        $snapshot = $documentRef->snapshot();

        if ($snapshot->exists()) {
            $documentRef->set($data, ['merge' => true]);
            return true;
        }

        return false;
    }

    public static function deleteDocument(string $collection, string $documentId): bool
    {
        self::initializeFirestore();

        $documentRef = self::$firestore->collection($collection)->document($documentId);
        $snapshot = $documentRef->snapshot();

        if ($snapshot->exists()) {
            $documentRef->delete();
            return true;
        }

        return false;
    }

    public static function queryCollection(string $collection, string $field, string $operator, $value): array
    {
        self::initializeFirestore();

        $query = self::$firestore->collection($collection)
            ->where($field, $operator, $value);

        $documents = [];
        foreach ($query->documents() as $document) {
            if ($document->exists()) {
                $documents[] = array_merge(['id' => $document->id()], $document->data());
            }
        }
        return $documents;
    }

    public static function getAcademicYearDocument(string $collection, string $email, string $academicYear, string $documentId): ?array
    {

        $documentId = null;
        self::initializeFirestore();

        $path = "$collection/$email/$academicYear";
        $docRef = $documentId
            ? self::$firestore->document("$path/$documentId")
            : self::$firestore->collection($path)->newDocument();

        if ($documentId) {
            $snapshot = $docRef->snapshot();
            return $snapshot->exists() ? $snapshot->data() : null;
        }

        return null;
    }

    public static function countWhere(string $collection, string $field, string $operator, $value): int
    {
        self::initializeFirestore();

        $query = self::$firestore->collection($collection)
            ->where($field, $operator, $value);

        $count = 0;

        foreach ($query->documents() as $document) {
            if ($document->exists()) {
                $count++;
            }
        }

        return $count;
    }

    public static function count(string $collection): int
    {
        self::initializeFirestore();

        $documents = self::$firestore->collection($collection)->documents();

        $count = 0;

        foreach ($documents as $document) {
            if ($document->exists()) {
                $count++;
            }
        }

        return $count;
    }

    //----------------------------------------------------------menu-----------------------------------------
    public static function getMenuItems(): array
    {
        return self::getCollection('menus');
    }

    public static function createMenuItem(array $data): DocumentReference
    {
        return self::syncDocumentAndGetRef('menus', $data);
    }

    public static function updateMenuItem(string $id, array $data): bool
    {
        return self::updateDocument('menus', $id, $data);
    }

    public static function deleteMenuItem(string $id): bool
    {
        return self::deleteDocument('menus', $id);
    }

    public static function getActiveMenuItems(): array
    {
        return self::queryCollection('menus', 'is_active', '=', true);
    }



    //---------------------------------------------------------Public Safety menus-----------------------------------------
    public static function getPublicSafetyMenuItems(string $collectionName)
    {
        return self::getCollection($collectionName);
    }

    public static function createPublicSafetyMenuItem(string $collectionName, array $data)
    {
        return self::syncDocumentAndGetRef($collectionName, $data);
    }

    public static function updatePublicSafetyMenuItem(string $collectionName, string $id, array $data)
    {
        return self::updateDocument($collectionName, $id, $data);
    }

    public static function deletePublicSafetyMenuItem(string $collectionName, string $id)
    {
        return self::deleteDocument($collectionName, $id);
    }

    public static function getPublicSafetyActiveMenuItems(string $collectionName)
    {
        return self::queryCollection($collectionName, 'is_active', '=', true);
    }

    //incidentTypes
    public static function getIncidentStatus(): array
    {
        return self::getCollection('publicSafety_incidentStatuses');
    }

    public static function createIncidentStatus(array $data): DocumentReference
    {
        return self::syncDocumentAndGetRef('publicSafety_incidentStatuses', $data);
    }

    // ---------------------------------------- Public Safety Form Items -----------------------------------------
    public static function getPublicSafetyFormItems(string $collectionName): array
    {
        return self::getCollection($collectionName);
    }

    public static function createPublicSafetyFormItem(string $collectionName, array $data)
    {
        return self::syncDocumentAndGetRef($collectionName, $data);
    }

    public static function updatePublicSafetyFormItem(string $collectionName, string $id, array $data)
    {
        return self::updateDocument($collectionName, $id, $data);
    }

    public static function deletePublicSafetyFormItem(string $collectionName, string $id)
    {
        return self::deleteDocument($collectionName, $id);
    }

    public static function getPublicSafetyActiveFormItems(string $collectionName)
    {
        return self::queryCollection($collectionName, 'is_active', '=', true);
    }

    // ---------------------------------------- Public Safety Table Items -----------------------------------------
    public static function getPublicSafetyTableItems(string $collectionName): array
    {
        return self::getCollection($collectionName);
    }

    public static function createPublicSafetyTableItem(string $collectionName, array $data)
    {
        return self::syncDocumentAndGetRef($collectionName, $data);
    }

    public static function updatePublicSafetyTableItem(string $collectionName, string $id, array $data)
    {
        return self::updateDocument($collectionName, $id, $data);
    }

    public static function deletePublicSafetyTableItem(string $collectionName, string $id)
    {
        return self::deleteDocument($collectionName, $id);
    }

    public static function getPublicSafetyActiveTableItems(string $collectionName)
    {
        return self::queryCollection($collectionName, 'is_active', '=', true);
    }
}
