<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Slug;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Slug\SlugGenerator;
use PHPUnit\Framework\Attributes\DataProvider;

final class SlugGeneratorTest extends TestCase
{
    public static function names(): array
    {
        return [
            'plain' => ['Open Day', 'open-day'],
            'punctuation' => ["  Year 1 -- Parents' evening!  ", 'year-1-parents-evening'],
            'welsh accents' => ['Ŵyl y Gwanwyn', 'wyl-y-gwanwyn'],
            'welsh circumflex' => ['Gŵyl Ddewi â chân', 'gwyl-ddewi-a-chan'],
            'only symbols' => ['!!!', 'event'],
            'empty' => ['', 'event'],
        ];
    }

    #[DataProvider('names')]
    public function testSlugify(string $name, string $expected): void
    {
        $slug = SlugGenerator::slugify($name);

        $this->assertSame($expected, $slug);
        $this->assertTrue(SlugGenerator::isValid($slug));
    }

    public function testVeryLongNamesAreCappedAndStillValid(): void
    {
        $slug = SlugGenerator::slugify(str_repeat('word ', 200));

        $this->assertLessThanOrEqual(200, strlen($slug));
        $this->assertTrue(SlugGenerator::isValid(rtrim($slug, '-')));
    }

    public static function invalid(): array
    {
        return [[''], ['Has Caps'], ['under_score'], ['-lead'], ['trail-'], ['double--hyphen'], ['sp ace'], ["x'; DROP"]];
    }

    #[DataProvider('invalid')]
    public function testIsValidRejects(string $slug): void
    {
        $this->assertFalse(SlugGenerator::isValid($slug));
    }

    public function testUniqueAppendsTheFirstFreeNumber(): void
    {
        $taken = ['open-day', 'open-day-2', 'open-day-3'];

        $this->assertSame('open-day-4', SlugGenerator::unique('open-day', fn($s) => in_array($s, $taken, true)));
        $this->assertSame('fete', SlugGenerator::unique('fete', fn($s) => in_array($s, $taken, true)));
    }
}
