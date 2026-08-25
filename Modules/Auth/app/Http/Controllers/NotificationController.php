<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 20))
            ->get();

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'data'         => $notifications,
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'success' => true,
            'count'   => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $notification): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $record = $user->notifications()->where('id', $notification)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        $record->markAsRead();

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function markAllAsRead(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function destroy(string $notification): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $record = $user->notifications()->where('id', $notification)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        $record->delete();

        return response()->json(['success' => true]);
    }
}
