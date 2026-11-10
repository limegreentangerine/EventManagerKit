<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use EventManagerKit\Entity\Event;

final class EventPage
{
    /** @param list<Event> $events */
    public function __construct(public readonly array $events, public readonly bool $hasMore) {}
}
