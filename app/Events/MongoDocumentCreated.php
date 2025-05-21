<?php

//MongoDocumentCreated.php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MongoDocumentCreated
{
    use Dispatchable, SerializesModels;

    public $collectionName;
    public $documentData;
    public $documentId;

    /**
     * Create a new event instance.
     *
     * @param string $collectionName
     * @param array $documentData
     * @param string|null $documentId
     */
    public function __construct(string $collectionName, array $documentData, string $documentId = null)
    {
        $this->collectionName = $collectionName;
        $this->documentData = $documentData;
        $this->documentId = $documentId;
    }
}
