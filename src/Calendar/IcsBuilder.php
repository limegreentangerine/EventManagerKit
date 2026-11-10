<?php

declare(strict_types=1);

namespace EventManagerKit\Calendar;

use DateTimeZone;
use DateTimeImmutable;
use EventManagerKit\Entity\Event;
use EventManagerKit\Time\SiteTimezone;

/**
 * Builds RFC 5545 iCalendar text for events.
 *
 * Timed events are written in UTC (the stored wall-clock time is read in the
 * site's timezone); all-day events are plain dates.
 */
final class IcsBuilder
{
    /**
     * @return list<string> unfolded content lines
     */
    private function vevent(Event $event, string $locale, string $host, ?string $url, DateTimeZone $zone, ?DateTimeImmutable $now): array
    {
        $stamp = ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $lines = ['BEGIN:VEVENT', 'UID:' . $event->getID() . '@' . $host, 'DTSTAMP:' . $stamp];

        if ($event->isAllDay()) {
            // DTEND for a DATE value is exclusive, so it is the day after the last day.
            $lines[] = 'DTSTART;VALUE=DATE:' . $event->getStartDate()->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $event->getEndDate()->modify('+1 day')->format('Ymd');
        } else {
            $lines[] = 'DTSTART:' . SiteTimezone::utc($event->getStartDate(), $zone);
            $lines[] = 'DTEND:' . SiteTimezone::utc($event->getEndDate(), $zone);
        }

        array_push($lines, ...$this->repeat($event, $zone));

        $lines[] = 'SUMMARY:' . self::escape($event->getName($locale));
        $description = trim(strip_tags((string) $event->getContent($locale)?->getDescription()));
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . self::escape($description);
        }
        if ($event->getLocation()) {
            $lines[] = 'LOCATION:' . self::escape($event->getLocation()->getFullName($locale));
        }
        if ($url) {
            $lines[] = 'URL:' . $url;
        }
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * RRULE and EXDATE lines for a repeating event, so calendar apps expand it themselves.
     *
     * @return list<string>
     */
    private function repeat(Event $event, DateTimeZone $zone): array
    {
        $rule = $event->getRecurrence();
        if (!$rule) {
            return [];
        }
        $allDay = $event->isAllDay();
        $parts = ['FREQ=' . strtoupper($rule->frequency)];
        if ($rule->interval > 1) {
            $parts[] = 'INTERVAL=' . $rule->interval;
        }
        if ($rule->until) {
            $until = new DateTimeImmutable($rule->until . ' 23:59:59');
            $parts[] = 'UNTIL=' . ($allDay ? $until->format('Ymd') : SiteTimezone::utc($until, $zone));
        }
        $lines = ['RRULE:' . implode(';', $parts)];
        if ($rule->exceptions) {
            $time = $event->getStartDate()->format('H:i:s');
            $values = array_map(fn(string $d) => $allDay ? str_replace('-', '', $d) : SiteTimezone::utc(new DateTimeImmutable($d . ' ' . $time), $zone), $rule->exceptions);
            $lines[] = 'EXDATE' . ($allDay ? ';VALUE=DATE' : '') . ':' . implode(',', $values);
        }

        return $lines;
    }
    /**
     * @param list<Event>             $events
     * @param callable(Event):?string $urlFor absolute public URL of an event, or null
     */
    public function calendar(array $events, string $name, string $locale, string $host, callable $urlFor, DateTimeZone $zone, ?DateTimeImmutable $now = null): string
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EventManagerKit//EN', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:' . self::escape($name)];
        foreach ($events as $event) {
            array_push($lines, ...$this->vevent($event, $locale, $host, $urlFor($event), $zone, $now));
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    public static function escape(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }

    /**
     * Folds a line at 75 octets without splitting a UTF-8 character.
     */
    public static function fold(string $line): string
    {
        $out = '';
        $limit = 75;
        while (strlen($line) > $limit) {
            $cut = $limit;
            // Back off while the next byte is a UTF-8 continuation byte.
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $out .= substr($line, 0, $cut) . "\r\n ";
            $line = substr($line, $cut);
            $limit = 74; // continuation lines start with a space
        }

        return $out . $line;
    }
}
