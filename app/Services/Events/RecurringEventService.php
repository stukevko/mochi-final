<?php

namespace App\Services\Events;

use App\Enums\EventRecurrence;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RecurringEventService
{
    public const MAX_OCCURRENCES = 52;

    /**
     * Create child instances for a series root (parent_event_id must be null).
     *
     * @return list<Event>
     */
    public function expandSeries(Event $parent): array
    {
        $recurrence = $parent->recurrence instanceof EventRecurrence
            ? $parent->recurrence
            : EventRecurrence::tryFrom((string) $parent->recurrence) ?? EventRecurrence::None;

        if (! $recurrence->isRepeating() || $parent->parent_event_id !== null) {
            return [];
        }

        $until = $parent->recurrence_until;
        if ($until === null) {
            return [];
        }

        $occurrences = $this->occurrenceStarts(
            $parent->starts_at,
            $recurrence,
            $until instanceof CarbonInterface ? $until : Carbon::parse($until),
        );

        // First occurrence is the parent itself.
        array_shift($occurrences);

        $created = [];
        $baseSlug = (string) $parent->slug;

        foreach ($occurrences as $startsAt) {
            $child = $parent->replicate([
                'slug',
                'parent_event_id',
                'recurrence',
                'recurrence_until',
                'created_at',
                'updated_at',
            ]);
            $child->forceFill([
                'starts_at' => $startsAt,
                'slug' => $this->uniqueSlugForOccurrence($baseSlug, $startsAt),
                'parent_event_id' => $parent->id,
                'recurrence' => EventRecurrence::None,
                'recurrence_until' => null,
            ]);
            $child->save();
            $created[] = $child;
        }

        return $created;
    }

    /**
     * Copy content fields from series root onto future child instances.
     *
     * @return int Number of updated children
     */
    public function syncFutureChildren(Event $parent): int
    {
        if ($parent->parent_event_id !== null) {
            return 0;
        }

        $fields = [
            'title',
            'description',
            'price',
            'game_type',
            'game_type_other',
            'calendar_color',
            'image_path',
            'status',
        ];

        $payload = $parent->only($fields);
        $updated = 0;

        $parent->children()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->each(function (Event $child) use ($payload, &$updated): void {
                $child->forceFill($payload)->save();
                $updated++;
            });

        return $updated;
    }

    /**
     * @return list<Carbon>
     */
    public function occurrenceStarts(
        CarbonInterface $firstStart,
        EventRecurrence $recurrence,
        CarbonInterface $until,
    ): array {
        $cursor = Carbon::parse($firstStart)->seconds(0);
        $untilEnd = Carbon::parse($until)->endOfDay();
        $starts = [];

        while ($cursor->lte($untilEnd) && count($starts) < self::MAX_OCCURRENCES) {
            $starts[] = $cursor->clone();
            $cursor = match ($recurrence) {
                EventRecurrence::Weekly => $cursor->addWeek(),
                EventRecurrence::Biweekly => $cursor->addWeeks(2),
                EventRecurrence::Monthly => $cursor->addMonthNoOverflow(),
                EventRecurrence::None => $cursor->addCentury(), // break loop safely
            };
        }

        return $starts;
    }

    public function uniqueSlugForOccurrence(string $baseSlug, CarbonInterface $startsAt): string
    {
        $base = Str::slug($baseSlug) ?: 'event';
        $candidate = $base.'-'.$startsAt->format('Y-m-d');
        $slug = $candidate;
        $i = 2;

        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $candidate.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
