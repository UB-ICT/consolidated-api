<?php

// app/Services/FirestoreService.php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use App\Jobs\SyncToFirestoreJob;

class FirestoreService
{
    protected $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => storage_path(env('FIREBASE_CREDENTIALS'))
        ]);
    }

    public function syncDocument(string $collection, array $data, string $documentId = null)
    {
        try {
            $collectionRef = $this->firestore->collection($collection);

            if ($documentId) {
                $collectionRef->document($documentId)->set($data);
            } else {
                $collectionRef->add($data);
            }
        } catch (\Exception $e) {
            \Log::error('Firestore sync failed: ' . $e->getMessage());
        }
    }
}