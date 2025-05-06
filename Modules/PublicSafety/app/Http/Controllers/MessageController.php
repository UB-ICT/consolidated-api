<?php

namespace Modules\PublicSafety\Http\Controllers;

use Modules\PublicSafety\Http\Controllers\Controller;
use Modules\PublicSafety\Models\UserCampus;
use Illuminate\Http\Request;
use Modules\PublicSafety\Models\Message;
use Modules\PublicSafety\Http\Requests\StoreMessageRequest;
use Modules\PublicSafety\Http\Requests\UpdateMessageRequest;
use Modules\PublicSafety\Transformers\MessageResource;
use Modules\PublicSafety\Transformers\MessageCollection;





use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return new MessageCollection(Message::paginate());
    }

    public function store(StoreMessageRequest $request)
    {
        return new UserCampus(Message::create($request->all()));
    }

    public function show(Message $message)
    {
        return new MessageResource($message);
    }
    public function update(UpdateMessageRequest $request, Message $message)
    {
        $message->update($request->all());
        return response()->json(['message' => 'Message updated successfully'], 200);
    }

    public function destroy(Request $request)
    {
        $message = Message::find($request->id);
        if ($message) {
            $message->delete();
            return response()->json(['message' => 'Message deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Message not found'], 404);
        }
    }
}
