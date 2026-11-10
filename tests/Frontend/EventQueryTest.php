<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Frontend;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Frontend\EventQuery;
use PHPUnit\Framework\Attributes\DataProvider;

final class EventQueryTest extends TestCase
{
    private const GUID = '8f18c5f8-dcf6-11f1-88a2-61102caee265';

    private function query(array $input): EventQuery
    {
        return EventQuery::fromArray($input, new DateTimeImmutable('2026-11-10 15:30'));
    }

    public function testDefaultsToCurrentMonthPageOneNoFilters(): void
    {
        $q = $this->query([]);

        $this->assertSame('2026-11-01 00:00:00', $q->monthStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-30 23:59:59', $q->monthEnd()->format('Y-m-d H:i:s'));
        $this->assertNull($q->typeId);
        $this->assertSame(1, $q->page);
    }

    public function testValidInputIsKept(): void
    {
        $q = $this->query(['month' => '2027-02', 'type' => strtoupper(self::GUID), 'location' => self::GUID, 'tag' => self::GUID, 'page' => '3']);

        $this->assertSame('2027-02-28 23:59:59', $q->monthEnd()->format('Y-m-d H:i:s'));
        $this->assertSame(self::GUID, $q->typeId);
        $this->assertSame(3, $q->page);
    }

    public static function badMonths(): array
    {
        return [['nonsense'], ['2026-13'], ['2026-1'], ['1999-12'], ['2101-01'], ['2026-11-01'], [['2026-11']], [null], [20261100]];
    }

    #[DataProvider('badMonths')]
    public function testBadMonthFallsBackToCurrent(mixed $month): void
    {
        $this->assertSame('2026-11', $this->query(['month' => $month])->month->format('Y-m'));
    }

    public static function badIds(): array
    {
        return [["' OR 1=1 --"], ['123'], [self::GUID . 'x'], [['a']], [''], [5]];
    }

    #[DataProvider('badIds')]
    public function testMalformedIdsAreDropped(mixed $id): void
    {
        $q = $this->query(['type' => $id, 'location' => $id, 'tag' => $id]);

        $this->assertNull($q->typeId);
        $this->assertNull($q->locationId);
        $this->assertNull($q->tagId);
    }

    public static function pages(): array
    {
        return [['0', 1], ['-4', 1], ['abc', 1], ['2', 2], ['99999', EventQuery::MAX_PAGE], [['1'], 1], [null, 1]];
    }

    #[DataProvider('pages')]
    public function testPageIsClamped(mixed $page, int $expected): void
    {
        $this->assertSame($expected, $this->query(['page' => $page])->page);
    }

    public function testToParamsKeepsFiltersAndOmitsPageOne(): void
    {
        $q = $this->query(['type' => self::GUID, 'page' => '4']);

        $this->assertSame(['month' => '2026-12', 'type' => self::GUID], $q->toParams(new DateTimeImmutable('2026-12-01')));
        $this->assertSame(['month' => '2026-11', 'type' => self::GUID, 'page' => '2'], $q->toParams(null, 2));
    }
}
