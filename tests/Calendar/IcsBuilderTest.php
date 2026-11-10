<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Calendar;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Event;
use EventManagerKit\Calendar\IcsBuilder;

final class IcsBuilderTest extends TestCase
{
    private function ics(Event $event): string
    {
        // Persisted events always have a GUID; set one the way Doctrine would.
        (new \ReflectionProperty($event, 'id'))->setValue($event, 'abc-123');
        $event->setContent('en_GB', 'Open day; bring, friends', 'Line one<br>Line two');

        return (new IcsBuilder())->calendar([$event], 'Site', 'en_GB', 'example.com', fn() => 'https://example.com/events/open-day', new DateTimeImmutable('2026-01-01 00:00', new \DateTimeZone('UTC')));
    }

    public function testTimedEvent(): void
    {
        $event = (new Event())->setDates(new DateTimeImmutable('2026-11-20 10:00'), new DateTimeImmutable('2026-11-20 12:30'));
        $ics = $this->ics($event);

        $this->assertStringContainsString("DTSTART:20261120T100000\r\n", $ics);
        $this->assertStringContainsString("DTEND:20261120T123000\r\n", $ics);
        $this->assertStringContainsString("DTSTAMP:20260101T000000Z\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Open day\; bring\, friends', $ics);
        $this->assertStringContainsString("URL:https://example.com/events/open-day\r\n", $ics);
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }

    public function testAllDayEndIsExclusive(): void
    {
        $event = (new Event())->setDates(new DateTimeImmutable('2026-12-02 00:00'), new DateTimeImmutable('2026-12-03 23:59:59'))->setAllDay(true);
        $ics = $this->ics($event);

        $this->assertStringContainsString("DTSTART;VALUE=DATE:20261202\r\n", $ics);
        $this->assertStringContainsString("DTEND;VALUE=DATE:20261204\r\n", $ics);
    }

    public function testEscapeNewlinesAndBackslash(): void
    {
        $this->assertSame('a\\\\b\nc', IcsBuilder::escape("a\\b\r\nc"));
    }

    public function testFoldKeepsLinesUnder76OctetsAndDoesNotSplitCharacters(): void
    {
        $line = 'SUMMARY:' . str_repeat('é', 100);
        $folded = IcsBuilder::fold($line);

        foreach (explode("\r\n", $folded) as $part) {
            $this->assertLessThanOrEqual(75, strlen($part));
            $this->assertTrue(mb_check_encoding($part, 'UTF-8'));
        }
        $this->assertSame($line, str_replace("\r\n ", '', $folded));
    }
}
