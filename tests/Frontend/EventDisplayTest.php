<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Frontend;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Event;
use EventManagerKit\Frontend\EventDisplay;

final class EventDisplayTest extends TestCase
{
    private function event(string $start, string $end, bool $allDay = false): Event
    {
        return (new Event())->setDates(new DateTimeImmutable($start), new DateTimeImmutable($end))->setAllDay($allDay);
    }

    public function testTimedSingleDay(): void
    {
        $this->assertSame('20 Nov 2026, 10:00 – 12:30', EventDisplay::dateRange($this->event('2026-11-20 10:00', '2026-11-20 12:30'), 'en_GB'));
    }

    public function testAllDaySingleAndMultiDay(): void
    {
        $this->assertSame('2 Dec 2026', EventDisplay::dateRange($this->event('2026-12-02 00:00', '2026-12-02 23:59:59', true), 'en_GB'));
        $this->assertSame('2 Dec 2026 – 3 Dec 2026', EventDisplay::dateRange($this->event('2026-12-02 00:00', '2026-12-03 23:59:59', true), 'en_GB'));
    }

    public function testMultiDayTimed(): void
    {
        $this->assertSame('1 Dec 2026 18:00 – 2 Dec 2026 09:00', EventDisplay::dateRange($this->event('2026-12-01 18:00', '2026-12-02 09:00'), 'en_GB'));
    }

    public function testUsesTheLocaleForMonthNames(): void
    {
        $this->assertStringContainsString('Tach', EventDisplay::dateRange($this->event('2026-11-20 10:00', '2026-11-20 11:00'), 'cy_GB'));
        $this->assertSame('Tachwedd 2026', EventDisplay::monthTitle(new DateTimeImmutable('2026-11-01'), 'cy_GB'));
    }

    public function testCalendarLinksCarryTheEventDetails(): void
    {
        $event = $this->event('2026-11-20 10:00', '2026-11-20 12:30')->setContent('en_GB', 'Open day');
        $links = EventDisplay::calendarLinks($event, 'en_GB', 'https://example.com/events/open-day');

        $this->assertStringContainsString('text=Open%20day', $links['google']);
        $this->assertStringContainsString('dates=20261120T100000%2F20261120T123000', $links['google']);
        $this->assertStringContainsString('subject=Open%20day', $links['outlook']);
        $this->assertStringContainsString('allday=false', $links['outlook']);
    }
}
