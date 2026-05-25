<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UBPortal\Models\Event;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::with('creator')
            ->orderBy('start_time')
            ->paginate(20);

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'banner_image' => 'nullable|string|max:500',
            'created_by' => 'required|uuid|exists:users,id',
        ]);

        $event = Event::create($data);

        return response()->json($event->load('creator'), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('creator'));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'banner_image' => 'nullable|string|max:500',
            'created_by' => 'sometimes|required|uuid|exists:users,id',
        ]);

        $event->update($data);

        return response()->json($event->fresh()->load('creator'));
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
