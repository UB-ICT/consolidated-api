<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Return the VAPID public key so browsers can subscribe.
     */
    public function vapidPublicKey(): JsonResponse
    {
        $key = config('webpush.vapid.public_key');

        if (empty($key)) {
            return response()->json(['success' => false, 'message' => 'VAPID public key not configured.'], 503);
        }

        return response()->json(['success' => true, 'public_key' => $key]);
    }

    /**
     * Save (or update) a browser push subscription for the authenticated user.
     *
     * Expected JSON body:
     *  {
     *    "endpoint": "https://...",
     *    "keys": { "p256dh": "...", "auth": "..." }
     *  }
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'endpoint'       => ['required', 'string', 'url'],
            'keys.p256dh'    => ['required', 'string'],
            'keys.auth'      => ['required', 'string'],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id'    => $user->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
            ]
        );

        return response()->json(['success' => true, 'data' => $subscription->only(['id', 'endpoint'])], 201);
    }

    /**
     * Delete a browser push subscription for the authenticated user.
     *
     * Expected JSON body:
     *  { "endpoint": "https://..." }
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $deleted = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Subscription not found.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
