<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FirestoreService;

class MessageController extends Controller
{
    /**
     * Firestore collection prefix
     */
    protected const COLLECTION_PREFIX = 'publicSafety_';

    /**
     * Messages collection name
     */
    protected string $messagesCollection = self::COLLECTION_PREFIX . 'messages';

    /**
     * Conversations collection name
     */
    protected string $conversationsCollection = self::COLLECTION_PREFIX . 'conversations';

    /**
     * ------------------------------------------------------------------
     * Send a message
     * POST /api/messages
     *
     * Supports:
     * - text, image, file, link, video
     * ------------------------------------------------------------------
     */
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'sender_id' => 'required|string',
            'content' => 'nullable|string',
            'type' => 'required|in:text,image,video,file,link',
            'metadata' => 'nullable|array',
        ]);

        try {
            // Firestore client instance
            $firestore = FirestoreService::firestore();

            // Create a new document in messages collection
            $messageRef = $firestore->collection($this->messagesCollection)->newDocument();

            $messageData = [
                'id' => $messageRef->id(),
                'conversation_id' => $request->conversation_id,
                'sender_id' => $request->sender_id,
                'content' => $request->input('content'),   // ✅ fix here
                'type' => $request->type,
                'metadata' => $request->metadata ?? null,
                'created_at' => now()->timestamp,
                'read_by' => [$request->sender_id], // sender automatically reads
                'deleted' => false,
            ];

            // Save the message
            $messageRef->set($messageData);

            // Update conversation's last message
            $firestore->collection($this->conversationsCollection)
                ->document($request->conversation_id)
                ->update([
                    ['path' => 'last_message', 'value' => $request->input('content')],
                    ['path' => 'last_message_at', 'value' => now()->timestamp],
                ]);

            return response()->json([
                'message' => 'Message sent successfully',
                'data' => $messageData,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Send message failed', [
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send message',
            ], 500);
        }
    }

    /**
     * ------------------------------------------------------------------
     * Get all messages for a conversation
     * GET /api/messages/{conversation_id}
     * ------------------------------------------------------------------
     */
    public function index(string $conversationId)
    {
        try {
            $firestore = FirestoreService::firestore();

            $messages = [];

            $documents = $firestore->collection($this->messagesCollection)
                ->where('conversation_id', '=', $conversationId)
                ->where('deleted', '==', false)
                ->orderBy('created_at', 'ASC')
                ->documents();

            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $messages[] = $doc->data();
                }
            }

            return response()->json([
                'data' => $messages,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Fetch messages failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fetch messages',
            ], 500);
        }
    }

    /**
     * ------------------------------------------------------------------
     * Mark a message as read
     * PATCH /api/messages/{message_id}/read
     * ------------------------------------------------------------------
     */
    public function markAsRead(string $messageId, Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
        ]);

        try {
            $firestore = FirestoreService::firestore();
            $messageRef = $firestore->collection($this->messagesCollection)->document($messageId);
            $snapshot = $messageRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json(['message' => 'Message not found'], 404);
            }

            $readBy = $snapshot->data()['read_by'] ?? [];

            if (!in_array($request->user_id, $readBy)) {
                $readBy[] = $request->user_id;
                $messageRef->update([
                    ['path' => 'read_by', 'value' => $readBy],
                ]);
            }

            return response()->json([
                'message' => 'Message marked as read',
            ]);

        } catch (\Throwable $e) {
            Log::error('Mark message read failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to mark message as read',
            ], 500);
        }
    }

    /**
     * ------------------------------------------------------------------
     * Soft delete a message
     * DELETE /api/messages/{message_id}
     * ------------------------------------------------------------------
     */
    public function destroy(string $messageId)
    {
        try {
            $firestore = FirestoreService::firestore();

            $firestore->collection($this->messagesCollection)
                ->document($messageId)
                ->update([
                    ['path' => 'deleted', 'value' => true],
                ]);

            return response()->json([
                'message' => 'Message deleted successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('Delete message failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to delete message',
            ], 500);
        }
    }

    /**
     * ------------------------------------------------------------------
     * Get media, files, or links for a conversation
     * GET /api/messages/{conversation_id}/media?type=image|file|link
     * ------------------------------------------------------------------
     */
    public function media(Request $request, string $conversationId)
    {
        // Validate query parameter
        $request->validate([
            'type' => 'required|in:image,file,link,video',
        ]);

        try {
            $firestore = FirestoreService::firestore();

            $type = $request->input('type');

            $documents = $firestore
                ->collection($this->messagesCollection)
                ->where('conversation_id', '=', $conversationId)
                ->where('type', '=', $type)
                ->orderBy('created_at', 'DESC')
                ->documents();

            $media = [];

            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $media[] = $doc->data();
                }
            }

            return response()->json([
                'data' => $media,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Fetch media failed', [
                'conversation_id' => $conversationId,
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fetch media',
            ], 500);
        }
    }
}
