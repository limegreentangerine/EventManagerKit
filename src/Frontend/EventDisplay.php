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

    /**
     * "Add to calendar" links for a web calendar. Times are sent as local
     * wall-clock values (no zone), matching how events are stored.
     *
     * @return array{google:string,outlook:string}
     */
    public static function calendarLinks(Event $event, string $locale, ?string $url): array
    {
        $allDay = $event->isAllDay();
        $title = $event->getName($locale);
        $details = trim(strip_tags((string) $event->getContent($locale)?->getDescription()));
        $where = $event->getLocation()?->getFullName($locale) ?? '';

        $google = 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $allDay
                ? $event->getStartDate()->format('Ymd') . '/' . $event->getEndDate()->modify('+1 day')->format('Ymd')
                : $event->getStartDate()->format('Ymd\THis') . '/' . $event->getEndDate()->format('Ymd\THis'),
            'details' => trim($details . "\n" . ($url ?? '')),
            'location' => $where,
        ], '', '&', PHP_QUERY_RFC3986);

        $outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $title,
            'startdt' => $event->getStartDate()->format($allDay ? 'Y-m-d' : 'Y-m-d\TH:i:s'),
            'enddt' => $event->getEndDate()->format($allDay ? 'Y-m-d' : 'Y-m-d\TH:i:s'),
            'allday' => $allDay ? 'true' : 'false',
            'body' => trim($details . "\n" . ($url ?? '')),
            'location' => $where,
        ], '', '&', PHP_QUERY_RFC3986);

        return ['google' => $google, 'outlook' => $outlook];
    }
}
