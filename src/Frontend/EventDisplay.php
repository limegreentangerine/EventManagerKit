<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use IntlDateFormatter;
use EventManagerKit\Entity\Event;

/**
 * Human-readable dates for an event, in a given locale.
 */
final class EventDisplay
{
    public static function dateRange(Event $event, string $locale): string
    {
        $day = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'd MMM y');
        $time = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'HH:mm');
        $start = $event->getStartDate();
        $end = $event->getEndDate();

        if ($event->isAllDay()) {
            return $event->isMultiDay() ? $day->format($start) . ' – ' . $day->format($end) : $day->format($start);
        }

        if ($event->isMultiDay()) {
            return $day->format($start) . ' ' . $time->format($start) . ' – ' . $day->format($end) . ' ' . $time->format($end);
        }

        return $day->format($start) . ', ' . $time->format($start) . ' – ' . $time->format($end);
    }

    public static function monthTitle(\DateTimeImmutable $month, string $locale): string
    {
        return (new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'LLLL y'))->format($month);
    }
}
