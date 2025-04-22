<?php

namespace Modules\Message\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Message\Models\Chat;
use Modules\Message\Models\Message;
use Modules\Message\Transformers\ChatResource;
use Modules\Message\Transformers\ChatCollection;
use Modules\Message\Http\Requests\StoreChatRequest;
use Modules\Message\Http\Requests\UpdateChatRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Events\ChatCreated;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's chats.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'sometimes|string',
            'is_group' => 'sometimes|boolean',
            'with_unread' => 'sometimes|boolean',
        ]);

        $chats = Chat::with(['users', 'latestMessage.sender'])
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                      ->orWhereHas('users', function ($q) use ($request) {
                          $q->where('name', 'like', '%'.$request->search.'%')
                            ->where('user_id', '!=', auth()->id());
                      });
                });
            })
            ->when($request->is_group, function ($query) use ($request) {
                $query->where('is_group', $request->is_group);
            })
            ->when($request->with_unread, function ($query) {
                $query->withCount(['messages as unread_messages_count' => function ($q) {
                    $q->where('sender_id', '!=', auth()->id())
                      ->whereNull('read_at');
                }]);
            })
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->limit(1);
            })
            ->paginate($request->per_page ?? 20);

        return new ChatCollection($chats);
    }

    /**
     * Store a newly created chat in storage.
     */
    public function store(StoreChatRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $chat = Chat::create([
                'name' => $request->name,
                'is_group' => $request->boolean('is_group', false),
            ]);

            // Attach users to the chat
            $userIds = $request->user_ids ?? [];
            if (!$request->is_group && count($userIds) === 1) {
                // For 1:1 chat, ensure both users are included
                $userIds[] = auth()->id();
            }
            $chat->users()->sync(array_unique($userIds));

            // Broadcast event if this is a group chat
            if ($chat->is_group) {
                broadcast(new ChatCreated($chat))->toOthers();
            }

            return new ChatResource($chat->load(['users', 'latestMessage']));
        });
    }

    /**
     * Display the specified chat.
     */
    public function show(Chat $chat)
    {
        $this->authorize('view', $chat);
        
        // Mark all messages as read when opening chat
        $chat->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return new ChatResource($chat->load(['users', 'messages.sender']));
    }

    /**
     * Update the specified chat in storage.
     */
    public function update(UpdateChatRequest $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $chat->update($request->only(['name']));

        if ($request->has('user_ids')) {
            $chat->users()->sync($request->user_ids);
        }

        return new ChatResource($chat->fresh()->load(['users', 'latestMessage']));
    }

    /**
     * Remove the specified chat from storage.
     */
    public function destroy(Chat $chat)
    {
        $this->authorize('delete', $chat);

        $chat->delete();

        return response()->json([
            'message' => 'Chat deleted successfully',
        ]);
    }

    /**
     * Get or create a 1:1 chat with another user
     */
    public function getOrCreatePrivateChat(Request $request, User $user)
    {
        $request->validate([
            'init_message' => 'sometimes|string',
        ]);

        // Check if a private chat already exists between these users
        $chat = Chat::where('is_group', false)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        if (!$chat) {
            $chat = Chat::create(['is_group' => false]);
            $chat->users()->attach([auth()->id(), $user->id]);
        }

        // Add initial message if provided
        if ($request->init_message) {
            $message = $chat->messages()->create([
                'sender_id' => auth()->id(),
                'content' => $request->init_message,
                'type' => 'text',
            ]);
            
            broadcast(new NewMessage($message))->toOthers();
        }

        return new ChatResource($chat->load(['users', 'latestMessage']));
    }

    /**
     * Get chat participants
     */
    public function participants(Chat $chat)
    {
        $this->authorize('view', $chat);
        
        return response()->json([
            'data' => $chat->users,
        ]);
    }

    /**
     * Add participants to group chat
     */
    public function addParticipants(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);
        
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $chat->users()->syncWithoutDetaching($request->user_ids);

        // Notify users they've been added to the chat
        // You would implement this notification logic

        return response()->json([
            'message' => 'Participants added successfully',
            'participants' => $chat->users,
        ]);
    }

    /**
     * Remove participant from group chat
     */
    public function removeParticipant(Chat $chat, User $user)
    {
        $this->authorize('update', $chat);
        
        // Can't remove yourself from 1:1 chat
        if (!$chat->is_group && $user->id === auth()->id()) {
            abort(403, 'Cannot leave a private chat');
        }

        $chat->users()->detach($user->id);

        // Notify the user they've been removed
        // You would implement this notification logic

        return response()->json([
            'message' => 'Participant removed successfully',
        ]);
    }
}