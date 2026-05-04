<?php

// app/Services/FirestoreService.php
//Purpose: Handles all direct interactions with Google Cloud Firestore

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;
use Illuminate\Support\Facades\Log;

class FirestoreService
{
    protected static $firestore;

    private final function __construct() {}


    protected static function initializeFirestore()
    {
        if (is_null(self::$firestore)) {

            self::$firestore = new FirestoreClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => storage_path(env('FIREBASE_CREDENTIALS_PATH'))
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
            Log::error('Error retrieving collection from Firestore', [
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
            Log::error("Firestore getCollectionWhere error: " . $e->getMessage());
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
