<?php

declare(strict_types=1);

namespace EventManagerKit\Import;

use DateTimeImmutable;

/**
 * One validated CSV row. All name arrays are keyed by locale (en_GB => ...).
 */
final class ImportRow
{
    /**
     * @param array<string,string>       $names
     * @param array<string,string>       $descriptions
     * @param array<string,string>       $location
     * @param array<string,string>       $subLocation
     * @param array<string,string>       $type
     * @param list<array<string,string>> $tags         one locale=>name map per tag
     */
    public function __construct(
        public readonly int $line,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
        public readonly bool $allDay,
        public readonly array $names,
        public readonly array $descriptions,
        public readonly array $location,
        public readonly array $subLocation,
        public readonly array $type,
        public readonly array $tags,
    ) {}
}
