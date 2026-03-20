<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    /**
     * Prefix used to namespace Firestore collections
     * Helps avoid collisions across modules
     */
    protected const COLLECTION_PREFIX = 'publicSafety_';

    /**
     * Firestore collection name for conversations
     */
    protected string $collectionName = self::COLLECTION_PREFIX . 'conversations';



    /**
     * Allowed conversation categories
     */
    protected const CATEGORIES = ['regular', 'anonymous', 'emergency'];
    protected $firestore;

    /**
     * Get all conversations for the logged-in user
     *
     * Supports:
     * - Regular conversations
     * - Anonymous conversations
     * - Emergency conversations
     *
     * Optional query param:
     * ?category=regular|anonymous|emergency
     */
    public function index(Request $request)
    {
        $userId = (string) $request->user()->id;
        $category = $request->query('categories');
        Log::info('category: ' . $category);
        Log::info('userId: ' . $userId);
        try {
            // Base query: only conversations where user is a participant
            $query = FirestoreService::firestore()
                ->collection($this->collectionName)
                ->where('participants', 'array-contains', $userId)
                ->where('isDeleted', '==', false);

            // Optional category filter
            if ($category && in_array($category, self::CATEGORIES)) {
                $query = $query->where('category', '=', $category);
            }

            // Order by most recent activity
            $documents = $query
                ->orderBy('lastMessageAt', 'DESC')
                ->documents();

            $conversations = [];

            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $conversations[] = array_merge(
                        ['id' => $doc->id()],
                        $doc->data()
                    );
                }
            }

            return response()->json($conversations);

        } catch (\Throwable $e) {
            Log::error('Fetch conversations failed', [
                'userId' => $userId,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fetch conversations'
            ], 500);
        }
    }


    /**
     * Create a new conversation
     *
     * Conversation categories:
     * - regular   → normal identified users
     * - anonymous → identity hidden from other participants
     * - emergency → high-priority, immediate response
     */
    public function store(Request $request)
    {
        $request->validate([
            'participants' => 'required|array|min:2',
            'category' => 'required|in:regular,anonymous,emergency',
        ]);

        try {
            $conversation = FirestoreService::firestore()
                ->collection($this->collectionName)
                ->add([
                    // All participant IDs as strings
                    'participants' => array_map('strval', $request->participants),

                    // Conversation type (private or group)
                    'type' => count($request->participants) > 2 ? 'group' : 'private',

                    // Conversation category (regular | anonymous | emergency)
                    'category' => $request->category,

                    // Metadata for message list sorting
                    'lastMessage' => null,
                    'lastMessageAt' => null,

                    'isDeleted' => false,   // ✅ ADD THIS

                    // Emergency flag (quick access for dashboards)
                    'isEmergency' => $request->category === 'emergency',

                    // Creation timestamp
                    'createdAt' => now(),
                ]);

            return response()->json([
                'id' => $conversation->id(),
                'message' => 'Conversation created successfully'
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Create conversation failed', [
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to create conversation'
            ], 500);
        }
    }

    /**
     * Get a single conversation by ID
     *
     * Used for:
     * - Opening a chat
     * - Checking category (regular / anonymous / emergency)
     */
    public function show(string $id, Request $request)
    {
        $userId = (string) $request->user()->id;

        try {
            $docRef = FirestoreService::firestore()
                ->collection($this->collectionName)
                ->document($id);

            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            // Authorization: must be participant
            $participants = $snapshot->data()['participants'] ?? [];
            if (!in_array($userId, $participants)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json(array_merge(
                ['id' => $snapshot->id()],
                $snapshot->data()
            ));

        } catch (\Throwable $e) {
            Log::error('Fetch conversation failed', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fetch conversation'
            ], 500);
        }
    }


    /**
     * Soft delete a conversation
     *
     * - Marks conversation as deleted
     * - Does NOT remove messages
     * - Safer for public safety & emergency data
     */
    public function destroy(string $id, Request $request)
    {
        $userId = (string) $request->user()->id;

        try {
            $docRef = FirestoreService::firestore()
                ->collection($this->collectionName)
                ->document($id);

            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            // Authorization check
            $participants = $snapshot->data()['participants'] ?? [];
            if (!in_array($userId, $participants)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Soft delete (audit-safe)
            $docRef->set([
                'isDeleted' => true,
                'deletedBy' => $userId,
                'deletedAt' => now(),
            ], ['merge' => true]);

            return response()->json([
                'message' => 'Conversation deleted successfully'
            ]);

        } catch (\Throwable $e) {
            Log::error('Delete conversation failed', [
                'conversationId' => $id,
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to delete conversation'
            ], 500);
        }
    }
}
