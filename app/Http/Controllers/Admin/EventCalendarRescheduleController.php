<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventCalendarRescheduleController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
        ]);

        $event = Event::query()->findOrFail($id);
        $event->starts_at = $request->date('starts_at');
        $event->save();

        return response()->json([
            'ok' => true,
            'starts_at' => $event->starts_at->toIso8601String(),
        ]);
    }
}
