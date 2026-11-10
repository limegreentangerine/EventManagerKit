<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use DateTimeZone;
use IntlDateFormatter;
use EventManagerKit\Entity\Event;
use EventManagerKit\Recurrence\Rule;
use EventManagerKit\Time\SiteTimezone;

/**
 * Human-readable dates for an event, in a given locale.
 */
final class EventDisplay
{
    /** An occurrence's own dates when given, else the event's first dates. */
    public static function dateRange(Event $event, string $locale, ?\DateTimeImmutable $start = null, ?\DateTimeImmutable $end = null): string
    {
        $day = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'd MMM y');
        $time = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'HH:mm');
        $start ??= $event->getStartDate();
        $end ??= $event->getEndDate();
        $multiDay = $start->format('Y-m-d') !== $end->format('Y-m-d');

        if ($event->isAllDay()) {
            return $multiDay ? $day->format($start) . ' – ' . $day->format($end) : $day->format($start);
        }

        if ($multiDay) {
            return $day->format($start) . ' ' . $time->format($start) . ' – ' . $day->format($end) . ' ' . $time->format($end);
        }

        return $day->format($start) . ', ' . $time->format($start) . ' – ' . $time->format($end);
    }

    /** "Repeats weekly until 31 Dec 2026", in the given locale. */
    public static function repeatSummary(Rule $rule, string $locale): string
    {
        $every = match ($rule->frequency) {
            'daily' => $rule->interval === 1 ? t('Repeats daily') : t('Repeats every %d days', $rule->interval),
            'weekly' => $rule->interval === 1 ? t('Repeats weekly') : t('Repeats every %d weeks', $rule->interval),
            default => $rule->interval === 1 ? t('Repeats monthly') : t('Repeats every %d months', $rule->interval),
        };
        if (!$rule->until) {
            return $every;
        }
        $day = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'd MMM y');

        return $every . ' ' . t('until %s', $day->format(new \DateTimeImmutable($rule->until)));
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
