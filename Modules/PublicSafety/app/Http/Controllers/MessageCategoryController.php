<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\MessageCategoryResource;
use Modules\PublicSafety\Transformers\MessageCategoryCollection;
use Modules\PublicSafety\Http\Requests\StoreMessageCategoryRequest;
use Modules\PublicSafety\Http\Requests\UpdateMessageCategoryRequest;
use Modules\PublicSafety\Models\MessageCategory;

class MessageCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new MessageCategoryCollection(MessageCategory::paginate());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMessageCategoryRequest $request)
    {
        return new MessageCategoryResource(MessageCategory::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(MessageCategory $messageCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MessageCategory $messageCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageCategoryRequest $request, MessageCategory $messageCategory)
    {
        $messageCategory->update($request->all());
        return response()->json(['message' => 'updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MessageCategory $messageCategory)
    {
        $messageCategory->delete();
        return response()->json(['message' => 'massageCategory deleted successfully'], 200);
    }
}
