<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use DateTimeZone;
use IntlDateFormatter;
use EventManagerKit\Entity\Event;
use EventManagerKit\Time\SiteTimezone;

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
     * "Add to calendar" links for a web calendar. Timed events are sent in UTC.
     *
     * @return array{google:string,outlook:string}
     */
    public static function calendarLinks(Event $event, string $locale, ?string $url, DateTimeZone $zone): array
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
                : SiteTimezone::utc($event->getStartDate(), $zone) . '/' . SiteTimezone::utc($event->getEndDate(), $zone),
            'details' => trim($details . "\n" . ($url ?? '')),
            'location' => $where,
        ], '', '&', PHP_QUERY_RFC3986);

        $outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $title,
            'startdt' => $allDay ? $event->getStartDate()->format('Y-m-d') : SiteTimezone::localise($event->getStartDate(), $zone)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'enddt' => $allDay ? $event->getEndDate()->format('Y-m-d') : SiteTimezone::localise($event->getEndDate(), $zone)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'allday' => $allDay ? 'true' : 'false',
            'body' => trim($details . "\n" . ($url ?? '')),
            'location' => $where,
        ], '', '&', PHP_QUERY_RFC3986);

        return ['google' => $google, 'outlook' => $outlook];
    }
}
