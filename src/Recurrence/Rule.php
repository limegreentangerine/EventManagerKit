<?php

declare(strict_types=1);

namespace EventManagerKit\Recurrence;

use InvalidArgumentException;

/**
 * A simple repeat rule: every N days, weeks or months from the event's own
 * start (same weekday for weekly, same day of the month for monthly), up to an
 * optional last date, skipping listed dates.
 */
final class Rule
{
    public const FREQUENCIES = ['daily', 'weekly', 'monthly'];
    public const MAX_EXCEPTIONS = 200;

    /** @var list<string> Y-m-d dates of occurrences to skip, sorted */
    public readonly array $exceptions;

    /**
     * @param  list<string>             $exceptions
     * @param  string|null              $until      last day an occurrence may start on (Y-m-d)
     * @throws InvalidArgumentException
     */
    public function __construct(public readonly string $frequency, public readonly int $interval = 1, public readonly ?string $until = null, array $exceptions = [])
    {
        if (!in_array($frequency, self::FREQUENCIES, true)) {
            throw new InvalidArgumentException('Unknown repeat frequency.');
        }
        if ($interval < 1 || $interval > 99) {
            throw new InvalidArgumentException('The repeat interval must be between 1 and 99.');
        }
        if ($until !== null && !self::isDate($until)) {
            throw new InvalidArgumentException('Invalid "repeat until" date.');
        }
        foreach ($exceptions as $date) {
            if (!self::isDate($date)) {
                throw new InvalidArgumentException('Invalid skipped date.');
            }
        }
        $exceptions = array_values(array_unique($exceptions));
        sort($exceptions);
        if (count($exceptions) > self::MAX_EXCEPTIONS) {
            throw new InvalidArgumentException('Too many skipped dates.');
        }
        $this->exceptions = $exceptions;
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /**
     * From dashboard form input; a blank frequency means "does not repeat".
     *
     * @param  string                   $skipped dates (Y-m-d) separated by spaces, commas or new lines
     * @throws InvalidArgumentException
     */
    public static function fromInput(string $frequency, mixed $interval, string $until, string $skipped): ?self
    {
        if ($frequency === '') {
            return null;
        }
        $interval = trim((string) $interval);

        return new self(
            $frequency,
            $interval === '' ? 1 : (ctype_digit($interval) ? (int) $interval : 0),
            trim($until) === '' ? null : trim($until),
            array_values(array_filter(preg_split('/[\s,]+/', $skipped) ?: [], 'strlen')),
        );
    }

    public function toJson(): string
    {
        return (string) json_encode(['frequency' => $this->frequency, 'interval' => $this->interval, 'until' => $this->until, 'exceptions' => $this->exceptions]);
    }

    /** Null for blank or unreadable stored data, so a bad row degrades to "does not repeat". */
    public static function fromJson(?string $json): ?self
    {
        $data = $json ? json_decode($json, true) : null;
        if (!is_array($data)) {
            return null;
        }
        try {
            return new self((string) ($data['frequency'] ?? ''), (int) ($data['interval'] ?? 1), $data['until'] ?? null, (array) ($data['exceptions'] ?? []));
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
