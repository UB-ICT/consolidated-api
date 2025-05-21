<?php

//syncToFirestore.php

namespace App\Listeners;

use App\Services\FirestoreService;
use App\Events\MongoDocumentCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue; //For better performance

class SyncToFirestore implements ShouldQueue
{

    protected $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }


    /**
     * Handle the event.
     */
    public function handle(MongoDocumentCreated $event)
    {
        try {
            $this->firestore->syncDocument(
                $event->collectionName,
                $event->documentData,
                $event->documentId
            );

            Log::info("Successfully synced document to Firestore", [
                'collection' => $event->collectionName,
                'document_id' => $event->documentId
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync document to Firestore: " . $e->getMessage(), [
                'collection' => $event->collectionName,
                'document_id' => $event->documentId,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}
