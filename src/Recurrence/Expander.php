<?php

declare(strict_types=1);

namespace EventManagerKit\Recurrence;

use DateTimeImmutable;
use EventManagerKit\Entity\Event;

/**
 * Turns an event (and its repeat rule) into dated occurrences within a window.
 * Works on the stored wall-clock times, so daylight saving never shifts them.
 */
final class Expander
{
    /** Hard stop on how far one call walks a series. */
    private const MAX_STEPS = 5000;

    /** Start of the n-th repeat (0 is the first), or null when the month has no such day. */
    private static function nth(Rule $rule, DateTimeImmutable $first, int $n): ?DateTimeImmutable
    {
        $k = $n * $rule->interval;

        return match ($rule->frequency) {
            'daily' => $first->modify("+{$k} days"),
            'weekly' => $first->modify('+' . ($k * 7) . ' days'),
            default => self::addMonths($first, $k),
        };
    }

    private static function addMonths(DateTimeImmutable $first, int $months): ?DateTimeImmutable
    {
        $day = (int) $first->format('j');
        $month = $first->modify('first day of this month')->modify("+{$months} months");

        return $day <= (int) $month->format('t') ? $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day) : null;
    }

    /**
     * Occurrences that end on or after $from and start on or before $to.
     * A null $to means "no upper bound" for a one-off event and one year after
     * $from for a repeating one.
     *
     * @return list<Occurrence>
     */
    public static function occurrences(Event $event, DateTimeImmutable $from, ?DateTimeImmutable $to = null, int $cap = 400): array
    {
        $start = $event->getStartDate();
        $end = $event->getEndDate();
        $rule = $event->getRecurrence();

        if (!$rule) {
            return $end >= $from && ($to === null || $start <= $to) ? [new Occurrence($event, $start, $end)] : [];
        }

        $to ??= $from->modify('+1 year');
        $until = $rule->until ? new DateTimeImmutable($rule->until . ' 23:59:59') : null;
        $duration = $start->diff($end);
        $unit = ['daily' => 86400, 'weekly' => 7 * 86400, 'monthly' => 28 * 86400][$rule->frequency];
        // Skip ahead to just before the window instead of walking from the first date.
        $n = max(0, intdiv($from->getTimestamp() - $start->getTimestamp() - ($end->getTimestamp() - $start->getTimestamp()), $rule->interval * $unit) - 1);

        $found = [];
        for ($steps = 0; $steps < self::MAX_STEPS && count($found) < $cap; $steps++, $n++) {
            $s = self::nth($rule, $start, $n);
            if ($s === null) {
                continue; // a month without that day (the 31st): skipped, as in iCalendar
            }
            if ($s > $to || ($until && $s > $until)) {
                break;
            }
            if (in_array($s->format('Y-m-d'), $rule->exceptions, true)) {
                continue;
            }
            $e = $s->add($duration);
            if ($e >= $from) {
                $found[] = new Occurrence($event, $s, $e);
            }
        }

        return $found;
    }
}
