<?php

namespace Modules\Message\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Events\NewMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Message\Models\Message;
use Modules\Message\Models\Chat;
use Modules\Message\Http\Requests\StoreMessageRequest;
use Modules\Message\Http\Requests\UpdateMessageRequest;
use Modules\Message\Transformers\MessageResource;
use Modules\Message\Transformers\MessageCollection;
use Illuminate\Support\Facades\DB;



use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'chat_id' => 'sometimes|exists:chats,id',
            'type' => 'sometimes|in:email,sms,notification,text,image,video,document,audio',
            'date_sent' => 'sometimes|date',
            'search' => 'sometimes|string',
        ]);

        $query = Message::with(['sender', 'chat'])
            ->when($request->chat_id, fn($q) => $q->where('chat_id', $request->chat_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date_sent, fn($q) => $q->whereDate('created_at', $request->date_sent))
            ->when($request->search, function ($q) use ($request) {
                $q->where('content', 'like', '%' . $request->search . '%');
            })
            ->orderBy('created_at', 'desc');

        return new MessageCollection($query->paginate($request->per_page ?? 20));
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
    public function store(StoreMessageRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['sender_id'] = auth()->id();

            // Handle file uploads if present
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime' => $file->getMimeType(),
                    ];
                }
                $data['attachments'] = $attachments;
            }

            $message = Message::create($data);

            // Broadcast the new message to other participants
            broadcast(new NewMessage($message))->toOthers();

            // Update chat's last message timestamp
            $message->chat->touch();

            return new MessageResource($message->load('sender'));
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        return new MessageResource($message->load(['sender', 'chat.users']));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Message $message)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message)
    {
        $this->authorize('update', $message);

        $message->update($request->validated());

        return new MessageResource($message->fresh()->load('sender'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);

        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully',
            'chat_id' => $message->chat_id,
        ]);
    }


    public function markAsRead(Message $message)
    {
        $this->authorize('view', $message);

        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return new MessageResource($message);
    }

    public function getCounts(Request $request)
    {
        $counts = Message::query()
            ->when($request->chat_id, fn($q) => $q->where('chat_id', $request->chat_id))
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json($counts);
    }

    public function search(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'query' => 'required|string|min:2',
        ]);

        $messages = Message::where('chat_id', $request->chat_id)
            ->where('content', 'like', '%' . $request->query . '%')
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return new MessageCollection($messages);
    }



    public function getTotalMessage(Message $message)
    {
        $message = Message::count();
        return response()->json(['total' => $message]);
    }
}
