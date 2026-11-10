<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Time;

use DateTimeZone;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Time\SiteTimezone;

final class SiteTimezoneTest extends TestCase
{
    public function testUtcFollowsDaylightSaving(): void
    {
        $london = new DateTimeZone('Europe/London');

        $this->assertSame('20260720T090000Z', SiteTimezone::utc(new DateTimeImmutable('2026-07-20 10:00'), $london)); // BST
        $this->assertSame('20261120T100000Z', SiteTimezone::utc(new DateTimeImmutable('2026-11-20 10:00'), $london)); // GMT
    }

    public function testLocaliseKeepsWallClockAndAddsOffset(): void
    {
        $date = SiteTimezone::localise(new DateTimeImmutable('2026-07-20 10:00', new DateTimeZone('UTC')), new DateTimeZone('Europe/London'));

        $this->assertSame('2026-07-20T10:00:00+01:00', $date->format(DATE_ATOM));
    }
}
