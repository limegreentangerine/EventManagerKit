<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Frontend\EndpointRequest;
use PHPUnit\Framework\Attributes\DataProvider;

final class EndpointRequestTest extends TestCase
{
    private const LOCALES = ['en_GB', 'cy_GB'];

    private function request(array $input): ?EndpointRequest
    {
        return EndpointRequest::fromArray($input, self::LOCALES, 'en_GB');
    }

    public function testValidCalendarRequest(): void
    {
        $r = $this->request(['view' => 'calendar', 'locale' => 'cy_GB', 'filters' => '1', 'month' => '2027-02', 'page' => '2']);

        $this->assertSame('calendar', $r->view);
        $this->assertSame('cy_GB', $r->locale);
        $this->assertTrue($r->showFilters);
        $this->assertSame('2027-02', $r->query->month->format('Y-m'));
        $this->assertSame(2, $r->query->page);
    }

    public static function badViews(): array
    {
        return [[null], [''], ['admin'], ['Calendar'], [['list']], [1]];
    }

    #[DataProvider('badViews')]
    public function testUnknownViewIsRejected(mixed $view): void
    {
        $this->assertNull($this->request(['view' => $view]));
        $this->assertNull($this->request([]));
    }

    public static function badLocales(): array
    {
        return [['fr_FR'], ['../../etc/passwd'], [''], [['cy_GB']], [null], ['EN_gb']];
    }

    #[DataProvider('badLocales')]
    public function testUnknownLocaleFallsBackToDefault(mixed $locale): void
    {
        $this->assertSame('en_GB', $this->request(['view' => 'list', 'locale' => $locale])->locale);
    }

    public static function limits(): array
    {
        return [['3', 3], ['0', 1], ['-5', 5], ['abc', 5], ['9999', 50], [null, 5], [['3'], 5], ['', 5]];
    }

    #[DataProvider('limits')]
    public function testLimitIsClamped(mixed $limit, int $expected): void
    {
        $this->assertSame($expected, $this->request(['view' => 'list', 'limit' => $limit])->limit);
    }

    public function testFiltersAreOffUnlessExactlyOne(): void
    {
        $this->assertFalse($this->request(['view' => 'calendar'])->showFilters);
        $this->assertFalse($this->request(['view' => 'calendar', 'filters' => 'yes'])->showFilters);
        $this->assertFalse($this->request(['view' => 'calendar', 'filters' => ['1']])->showFilters);
    }

    public function testMalformedFilterIdsNeverReachTheQuery(): void
    {
        $r = $this->request(['view' => 'calendar', 'type' => "' OR 1=1 --", 'month' => 'zzz']);

        $this->assertNull($r->query->typeId);
    }

    public function testFeaturedViewIsServedWithClampedLimit(): void
    {
        $r = $this->request(['view' => 'featured', 'limit' => '9999']);

        $this->assertSame('featured', $r->view);
        $this->assertSame(50, $r->limit);
    }

    public function testEventViewNeedsAWellFormedEventId(): void
    {
        $id = '0F8FAD5B-D9CB-469F-A165-70867728950E';

        $this->assertSame(strtolower($id), $this->request(['view' => 'event', 'event' => $id])->eventId);
        $this->assertNull($this->request(['view' => 'event']));
        $this->assertNull($this->request(['view' => 'event', 'event' => "' OR 1=1 --"]));
        $this->assertNull($this->request(['view' => 'event', 'event' => [$id]]));
    }
}
