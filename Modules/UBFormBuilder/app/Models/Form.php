<?php

namespace Modules\UBFormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\FirestoreFormBuilderService;

class Form extends Model
{
    use HasFactory;
    protected $fillable = [];
    protected $firestore;

     public function __construct(FirestoreFormBuilderService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function createForm(array $data)
    {
        return $this->firestore->createForm($data);
    }

    public function findForm(string $id)
    {
        return $this->firestore->getForm($id);
    }

    public function updateForm(string $id, array $data)
    {
        return $this->firestore->updateForm($id, $data);
    }

     public function deleteForm(string $id)
    {
        return $this->firestore->deleteForm($id);
    }

    public function allForms()
    {
        return $this->firestore->getAllForms();
    }

}
