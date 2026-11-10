<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Translation;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\EventContent;
use EventManagerKit\Translation\Translations;

final class TranslationsTest extends TestCase
{
    private function row(string $locale): EventContent
    {
        return (new EventContent())->setLocale($locale)->setName($locale);
    }

    public function testExactLocaleWins(): void
    {
        $rows = [$this->row('en_GB'), $this->row('cy_GB')];

        $this->assertSame('cy_GB', Translations::pick($rows, 'cy_GB')->getName());
    }

    public function testSameLanguageMatchesWhenRegionDiffers(): void
    {
        $rows = [$this->row('en_GB'), $this->row('cy_GB')];

        $this->assertSame('cy_GB', Translations::pick($rows, 'cy')->getName());
        $this->assertSame('en_GB', Translations::pick($rows, 'en_US')->getName());
    }

    public function testFallsBackToDefaultLocale(): void
    {
        $rows = [$this->row('cy_GB'), $this->row('en_GB')];

        $this->assertSame('en_GB', Translations::pick($rows, 'fr_FR', 'en_GB')->getName());
    }

    public function testFallsBackToAnyRowWhenDefaultMissing(): void
    {
        $rows = [$this->row('cy_GB')];

        $this->assertSame('cy_GB', Translations::pick($rows, 'fr_FR', 'en_GB')->getName());
    }

    public function testEmptyReturnsNull(): void
    {
        $this->assertNull(Translations::pick([], 'en_GB'));
    }
}
