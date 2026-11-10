<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Enum;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Enum\TimeOfDay;
use PHPUnit\Framework\Attributes\DataProvider;

final class TimeOfDayTest extends TestCase
{
    #[DataProvider('hours')]
    public function testFromHour(int $hour, TimeOfDay $expected): void
    {
        $this->assertSame($expected, TimeOfDay::fromHour($hour));
    }

    public static function hours(): array
    {
        return [
            'midnight' => [0, TimeOfDay::Morning],
            'before noon' => [11, TimeOfDay::Morning],
            'noon' => [12, TimeOfDay::Afternoon],
            // The original left 15:00 unmapped.
            'three pm' => [15, TimeOfDay::Afternoon],
            'late afternoon' => [16, TimeOfDay::Afternoon],
            'five pm' => [17, TimeOfDay::Evening],
            'last hour' => [23, TimeOfDay::Evening],
        ];
    }
}
