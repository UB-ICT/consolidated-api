<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\DocumentReference;

class FirestoreFormBuilderService
{
    protected static $firestore;
    private final function __construct() {}

    protected static function initializeFirestore()
    {
        if (is_null(self::$firestore)) {
            self::$firestore = new FirestoreClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => storage_path(env('FIREBASE_CREDENTIALS'))
            ]);
        }
    }

    // Forms collection operations
    public function getFormsCollection()
    {
        return $this->firestore->collection('forms');
    }

    public function createForm(array $formData)
    {
        $collection = $this->getFormsCollection();
        $document = $collection->add($formData);
        return $document->id();
    }

    public function getForm(string $formId)
    {
        $document = $this->getFormsCollection()->document($formId)->snapshot();
        return $document->exists() ? $document->data() : null;
    }

    public function updateForm(string $formId, array $formData)
    {
        $this->getFormsCollection()->document($formId)->set($formData, ['merge' => true]);
        return true;
    }

    public function deleteForm(string $formId)
    {
        $this->getFormsCollection()->document($formId)->delete();
        return true;
    }

    public function getAllForms()
    {
        $documents = $this->getFormsCollection()->documents();
        $forms = [];

        foreach ($documents as $document) {
            if ($document->exists()) {
                $forms[] = [
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
        }

        return $forms;
    }
}
