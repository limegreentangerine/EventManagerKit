<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Recurrence;

use DateTimeImmutable as D;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Event;
use EventManagerKit\Recurrence\{Expander, Rule};

final class ExpanderTest extends TestCase
{
    private function event(string $start, string $end, ?Rule $rule = null): Event
    {
        return (new Event())->setDates(new D($start), new D($end))->setRecurrence($rule);
    }

    /** @return list<string> */
    private function starts(Event $event, string $from, ?string $to = null, int $cap = 400): array
    {
        return array_map(fn($o) => $o->start->format('Y-m-d H:i'), Expander::occurrences($event, new D($from), $to ? new D($to) : null, $cap));
    }

    public function testOneOffEventIsItsOwnOccurrence(): void
    {
        $event = $this->event('2026-11-20 10:00', '2026-11-20 11:00');

        $this->assertSame(['2026-11-20 10:00'], $this->starts($event, '2026-11-01', '2026-11-30'));
        $this->assertSame([], $this->starts($event, '2026-12-01', '2026-12-31'));
        $this->assertSame([], $this->starts($event, '2026-10-01', '2026-10-31'));
    }

    public function testWeeklyKeepsWeekdayAndTimeAcrossDaylightSaving(): void
    {
        $event = $this->event('2026-10-22 18:00', '2026-10-22 19:00', new Rule('weekly'));

        $this->assertSame(['2026-10-22 18:00', '2026-10-29 18:00', '2026-11-05 18:00'], $this->starts($event, '2026-10-01', '2026-11-06'));
    }

    public function testIntervalUntilAndExceptions(): void
    {
        $event = $this->event('2026-01-01 09:00', '2026-01-01 10:00', new Rule('daily', 2, '2026-01-09', ['2026-01-05']));

        $this->assertSame(['2026-01-01 09:00', '2026-01-03 09:00', '2026-01-07 09:00', '2026-01-09 09:00'], $this->starts($event, '2026-01-01', '2026-02-01'));
    }

    public function testMonthlyOn31stSkipsShortMonths(): void
    {
        $event = $this->event('2026-01-31 12:00', '2026-01-31 13:00', new Rule('monthly'));

        $this->assertSame(['2026-01-31 12:00', '2026-03-31 12:00', '2026-05-31 12:00'], $this->starts($event, '2026-01-01', '2026-06-01'));
    }

    public function testWindowFarFromTheStartStillFindsOccurrences(): void
    {
        $event = $this->event('2016-01-04 08:00', '2016-01-04 09:00', new Rule('weekly'));

        $this->assertSame(['2026-11-02 08:00', '2026-11-09 08:00'], $this->starts($event, '2026-11-01', '2026-11-10'));
    }

    public function testMultiDayOccurrenceOverlappingTheWindowStartIsIncluded(): void
    {
        $event = $this->event('2026-11-01 00:00', '2026-11-03 23:59:59', new Rule('weekly'));

        $this->assertSame(['2026-11-01 00:00', '2026-11-08 00:00'], $this->starts($event, '2026-11-03', '2026-11-10'));
    }

    public function testRepeatingEventWithoutAnEndIsBoundedToAYearAndCapped(): void
    {
        $event = $this->event('2026-01-01 09:00', '2026-01-01 10:00', new Rule('daily'));

        $this->assertCount(365, $this->starts($event, '2026-01-01'));
        $this->assertCount(5, $this->starts($event, '2026-01-01', null, 5));
    }

    public function testRuleInputAndJsonRoundTrip(): void
    {
        $this->assertNull(Rule::fromInput('', 1, '', ''));
        $rule = Rule::fromInput('weekly', '2', '2026-12-31', "2026-12-25, 2026-12-24\n2026-12-25");

        $this->assertSame(['2026-12-24', '2026-12-25'], $rule->exceptions);
        $this->assertEquals($rule, Rule::fromJson($rule->toJson()));
        $this->assertNull(Rule::fromJson('garbage'));
    }

    /** @dataProvider badRules */
    public function testInvalidRulesAreRejected(string $freq, string $interval, string $until, string $skipped): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Rule::fromInput($freq, $interval, $until, $skipped);
    }

    public static function badRules(): iterable
    {
        yield 'unknown frequency' => ['yearly', '1', '', ''];
        yield 'zero interval' => ['daily', '0', '', ''];
        yield 'text interval' => ['daily', 'x', '', ''];
        yield 'bad until' => ['daily', '1', '2026-02-30', ''];
        yield 'bad skipped date' => ['daily', '1', '', '2026-13-01'];
    }
}
