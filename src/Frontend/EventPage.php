<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use EventManagerKit\Entity\Event;
use EventManagerKit\Recurrence\Occurrence;

final class EventPage
{
    /** @param list<Occurrence> $occurrences */
    public function __construct(public readonly array $occurrences, public readonly bool $hasMore) {}

    /**
     * The distinct events behind the occurrences, in order (a repeating event once).
     *
     * @return list<Event>
     */
    public function events(): array
    {
        $events = [];
        foreach ($this->occurrences as $occurrence) {
            $events[$occurrence->event->getID()] ??= $occurrence->event;
        }

        return array_values($events);
    }
}
