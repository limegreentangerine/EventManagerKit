<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use DateTimeImmutable;

/**
 * Visitor-supplied calendar filters (query string), sanitised.
 *
 * Nothing here throws: a tampered or stale link degrades to the defaults
 * (current month, no filter, page 1) instead of a 500. Only values of the
 * right shape reach the database.
 */
final class EventQuery
{
    private const GUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    public const PER_PAGE = 10;
    public const MAX_PAGE = 500;

    public function __construct(
        public readonly DateTimeImmutable $month,
        public readonly ?string $typeId = null,
        public readonly ?string $locationId = null,
        public readonly ?string $tagId = null,
        public readonly int $page = 1,
    ) {}

    private static function month(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m', $value);
        // Reject malformed input and silly years that make date maths meaningless.
        if (!$date || $date->format('Y-m') !== $value || (int) $date->format('Y') < 2000 || (int) $date->format('Y') > 2100) {
            return null;
        }

        return $date;
    }

    private static function guid(mixed $value): ?string
    {
        return is_string($value) && preg_match(self::GUID, $value) ? strtolower($value) : null;
    }

    private static function page(mixed $value): int
    {
        if (!is_string($value) || !ctype_digit($value)) {
            return 1;
        }

        return max(1, min(self::MAX_PAGE, (int) $value));
    }

    /**
     * @param array<string,mixed> $input raw $_GET-style array (keys: month, type, location, tag, page)
     */
    public static function fromArray(array $input, ?DateTimeImmutable $now = null): self
    {
        $now ??= new DateTimeImmutable();

        return new self(
            self::month($input['month'] ?? null) ?? $now->modify('first day of this month')->setTime(0, 0),
            self::guid($input['type'] ?? null),
            self::guid($input['location'] ?? null),
            self::guid($input['tag'] ?? null),
            self::page($input['page'] ?? null),
        );
    }

    public function monthStart(): DateTimeImmutable
    {
        return $this->month;
    }

    public function monthEnd(): DateTimeImmutable
    {
        return $this->month->modify('first day of next month')->modify('-1 second');
    }

    /**
     * Query-string parameters to link to another month/page, keeping filters.
     *
     * @return array<string,string>
     */
    public function toParams(?DateTimeImmutable $month = null, ?int $page = null): array
    {
        return array_filter([
            'month' => ($month ?? $this->month)->format('Y-m'),
            'type' => $this->typeId,
            'location' => $this->locationId,
            'tag' => $this->tagId,
            'page' => ($page ?? 1) > 1 ? (string) $page : null,
        ], fn($v) => $v !== null);
    }
}
