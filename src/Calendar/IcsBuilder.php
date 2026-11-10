<?php

declare(strict_types=1);

namespace EventManagerKit\Calendar;

use DateTimeImmutable;
use EventManagerKit\Entity\Event;

/**
 * Builds RFC 5545 iCalendar text for events.
 *
 * Times are written as "floating" local times (no zone), the same wall-clock
 * values the dashboard stores. shortcut: floating times, upgrade when events
 * get a timezone.
 */
final class IcsBuilder
{
    /**
     * @return list<string> unfolded content lines
     */
    private function vevent(Event $event, string $locale, string $host, ?string $url, ?DateTimeImmutable $now): array
    {
        $stamp = ($now ?? new DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $lines = ['BEGIN:VEVENT', 'UID:' . $event->getID() . '@' . $host, 'DTSTAMP:' . $stamp];

        if ($event->isAllDay()) {
            // DTEND for a DATE value is exclusive, so it is the day after the last day.
            $lines[] = 'DTSTART;VALUE=DATE:' . $event->getStartDate()->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $event->getEndDate()->modify('+1 day')->format('Ymd');
        } else {
            $lines[] = 'DTSTART:' . $event->getStartDate()->format('Ymd\THis');
            $lines[] = 'DTEND:' . $event->getEndDate()->format('Ymd\THis');
        }

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
     * @param list<Event>             $events
     * @param callable(Event):?string $urlFor absolute public URL of an event, or null
     */
    public function calendar(array $events, string $name, string $locale, string $host, callable $urlFor, ?DateTimeImmutable $now = null): string
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EventManagerKit//EN', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:' . self::escape($name)];
        foreach ($events as $event) {
            array_push($lines, ...$this->vevent($event, $locale, $host, $urlFor($event), $now));
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
