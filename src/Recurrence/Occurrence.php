<?php

declare(strict_types=1);

namespace EventManagerKit\Recurrence;

use DateTimeImmutable;
use EventManagerKit\Entity\Event;

/** One dated showing of an event (a one-off event has exactly one). */
final class Occurrence
{
    public function __construct(public readonly Event $event, public readonly DateTimeImmutable $start, public readonly DateTimeImmutable $end) {}
}
