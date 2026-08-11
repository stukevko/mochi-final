<?php

namespace Tests\Feature;

use App\Enums\EventRecurrence;
use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Event;
use App\Services\Events\RecurringEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringEventServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_series_expands_child_occurrences(): void
    {
        Carbon::setTestNow('2026-08-14 18:00:00'); // Friday

        $parent = $this->makeParent(
            EventRecurrence::Weekly,
            '2026-08-14 18:00:00',
            '2026-09-04',
        );

        $children = app(RecurringEventService::class)->expandSeries($parent);

        $this->assertCount(3, $children);
        $this->assertSame(
            ['2026-08-21', '2026-08-28', '2026-09-04'],
            collect($children)->map(fn (Event $e) => $e->starts_at->toDateString())->all(),
        );
        $this->assertTrue($children[0]->isSeriesChild());
        $this->assertSame($parent->id, $children[0]->parent_event_id);
        $this->assertSame(EventRecurrence::None, $children[0]->recurrence);
        $this->assertStringContainsString('friday-night-draft-2026-08-21', $children[0]->slug);
    }

    public function test_biweekly_respects_until_and_max_occurrences(): void
    {
        $parent = $this->makeParent(
            EventRecurrence::Biweekly,
            '2026-01-01 19:00:00',
            '2026-12-31',
        );

        $children = app(RecurringEventService::class)->expandSeries($parent);

        // First + children <= 52
        $this->assertLessThanOrEqual(RecurringEventService::MAX_OCCURRENCES - 1, count($children));
        $this->assertSame('2026-01-15', $children[0]->starts_at->toDateString());
        $this->assertTrue(
            collect($children)->every(fn (Event $e) => $e->starts_at->lte(Carbon::parse('2026-12-31')->endOfDay()))
        );
    }

    public function test_sync_future_children_updates_content_not_dates(): void
    {
        Carbon::setTestNow('2026-08-20 12:00:00');

        $parent = $this->makeParent(
            EventRecurrence::Weekly,
            '2026-08-14 18:00:00',
            '2026-09-04',
        );
        $children = app(RecurringEventService::class)->expandSeries($parent);
        $pastChild = $children[0]; // 21.08 still future relative to 20.08
        $futureChild = $children[2]; // 04.09

        Carbon::setTestNow('2026-08-25 12:00:00'); // past 21.08, before 28.08 & 04.09

        $parent->forceFill([
            'title' => 'Neuer Draft-Titel',
            'price' => 12.50,
        ])->save();

        $updated = app(RecurringEventService::class)->syncFutureChildren($parent->fresh());

        $this->assertSame(2, $updated);
        $pastChild->refresh();
        $futureChild->refresh();

        $this->assertSame('Friday Night Draft', $pastChild->title);
        $this->assertSame('Neuer Draft-Titel', $futureChild->title);
        $this->assertSame('2026-09-04', $futureChild->starts_at->toDateString());
    }

    public function test_public_feed_includes_series_children(): void
    {
        $parent = $this->makeParent(
            EventRecurrence::Weekly,
            now()->addDays(2)->setTime(18, 0)->toDateTimeString(),
            now()->addWeeks(3)->toDateString(),
        );
        app(RecurringEventService::class)->expandSeries($parent);

        $response = $this->getJson(route('events.feed'));

        $response->assertOk();
        $titles = collect($response->json())->pluck('title');
        $this->assertGreaterThanOrEqual(2, $titles->filter(fn ($t) => $t === 'Friday Night Draft')->count());
    }

    public function test_deleting_series_root_removes_children(): void
    {
        $parent = $this->makeParent(
            EventRecurrence::Weekly,
            '2026-08-14 18:00:00',
            '2026-08-28',
        );
        app(RecurringEventService::class)->expandSeries($parent);

        $this->assertDatabaseCount('events', 3);
        $parent->delete();
        $this->assertDatabaseCount('events', 0);
    }

    private function makeParent(EventRecurrence $recurrence, string $startsAt, string $until): Event
    {
        $event = new Event;
        $event->forceFill([
            'title' => 'Friday Night Draft',
            'slug' => 'friday-night-draft',
            'description' => '<p>Draft Abend</p>',
            'starts_at' => $startsAt,
            'recurrence' => $recurrence,
            'recurrence_until' => $until,
            'price' => 10,
            'game_type' => GameType::Pokemon,
            'status' => EventStatus::Active,
        ])->save();

        return $event->refresh();
    }
}
