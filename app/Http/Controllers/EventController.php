<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class EventController extends Controller
{
    public function calendar(): View
    {
        return view('events.calendar', [
            'gameTypes' => GameType::casesForSelect(),
        ]);
    }

    public function feed(): JsonResponse
    {
        $ttlSeconds = (int) config('mochicards.events_feed_cache_seconds', 600);

        $payload = Cache::remember(Event::FEED_CACHE_KEY, now()->addSeconds(max(60, $ttlSeconds)), function () {
            $events = Event::query()
                ->active()
                ->where('starts_at', '>=', now()->startOfMonth()->subMonths(3))
                ->where('starts_at', '<=', now()->addMonths(18))
                ->orderBy('starts_at')
                ->get();

            return $events->map(function (Event $event) {
                $start = $event->starts_at->clone();
                $end = $start->clone()->addHours(2);
                $style = $event->calendarFeedColors();

                return [
                    'id' => (string) $event->getKey(),
                    'title' => $event->title,
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'url' => route('events.show', $event),
                    'backgroundColor' => $style['backgroundColor'],
                    'borderColor' => $style['borderColor'],
                    'textColor' => $style['textColor'],
                ];
            })->values()->all();
        });

        $maxAge = (int) config('mochicards.events_feed_http_max_age', 300);

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age='.max(60, $maxAge));
    }

    public function index(Request $request): View
    {
        $gameFilter = $request->query('game');
        $showPast = $request->boolean('past');

        $events = Event::query()
            ->active()
            ->when(
                ! $showPast,
                fn ($q) => $q->where('starts_at', '>=', now()->startOfDay()),
            )
            ->when(
                $gameFilter !== null && $gameFilter !== '' && GameType::tryFrom((string) $gameFilter),
                fn ($q) => $q->where('game_type', $gameFilter),
            )
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'gameTypes' => GameType::casesForSelect(),
            'activeGame' => $gameFilter,
            'showPast' => $showPast,
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->status === EventStatus::Active, 404);

        return view('events.show', [
            'event' => $event,
        ]);
    }
}
