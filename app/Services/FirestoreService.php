<?php

// app/Services/FirestoreService.php
//Purpose: Handles all direct interactions with Google Cloud Firestore

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;

use App\Jobs\SyncToFirestoreJob;

class FirestoreService
{
    private static $firestore;

    private final function __construct()
    {
    }

    // This method is responsible for syncing a document to Firestore
    // It takes the collection name, document data, and an optional document ID

    // public static function syncDocument(string $collection, array $data, string $documentId = null)
    // {
    //     try {
    //         if (is_null(self::$firestore)) {
    //             // Initialize Firestore client if not already initialized
    //             self::$firestore = new FirestoreClient([
    //                 'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
    //                 'keyFilePath' => storage_path(env('FIREBASE_CREDENTIALS'))
    //             ]);
    //         }
    //         // Get a reference to the Firestore collection
    //         $collectionRef = self::$firestore->collection($collection);

    //         // If a document ID is provided, it updates the existing document
    //         // If not, it creates a new document in the specified collection
    //         // It uses the FirestoreClient to interact with Firestore
    //         if ($documentId) {
    //             $collectionRef->document($documentId)->set($data);
    //         } else {
    //             $collectionRef->add($data);
    //         }
    //         // It also handles exceptions and logs errors if any occur during the sync process
    //     } catch (\Exception $e) {
    //         \Log::error('Firestore sync failed: ' . $e->getMessage());
    //     }
    // }

    private static function initializeFirestore()
    {
        if (is_null(self::$firestore)) {
            self::$firestore = new FirestoreClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => storage_path(env('FIREBASE_CREDENTIALS'))
            ]);
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

}