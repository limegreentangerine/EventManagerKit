<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Slug;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Event;
use EventManagerKit\Slug\SlugService;

final class SlugServiceTest extends TestCase
{
    private function service(array $inDatabase = []): SlugService
    {
        return new SlugService(fn(string $locale, string $slug) => in_array($locale . '|' . $slug, $inDatabase, true));
    }

    public function testFillsEachLocaleFromItsOwnName(): void
    {
        $event = (new Event())->setContent('en_GB', 'Spring Fair')->setContent('cy_GB', 'Ffair y Gwanwyn');
        $this->service()->fill($event);

        $this->assertSame('spring-fair', $event->getSlug('en_GB'));
        $this->assertSame('ffair-y-gwanwyn', $event->getSlug('cy_GB'));
    }

    public function testSlugTakenInDatabaseGetsANumber(): void
    {
        $event = (new Event())->setContent('en_GB', 'Spring Fair');
        $this->service(['en_GB|spring-fair'])->fill($event);

        $this->assertSame('spring-fair-2', $event->getSlug('en_GB'));
    }

    public function testTheSameSlugInAnotherLocaleIsNotACollision(): void
    {
        $event = (new Event())->setContent('cy_GB', 'Spring Fair');
        $this->service(['en_GB|spring-fair'])->fill($event);

        $this->assertSame('spring-fair', $event->getSlug('cy_GB'));
    }

    public function testUnflushedEventsInTheSameRunDoNotCollide(): void
    {
        $service = $this->service();
        $a = (new Event())->setContent('en_GB', 'Open Day');
        $b = (new Event())->setContent('en_GB', 'Open Day');
        $service->fill($a);
        $service->fill($b);

        $this->assertSame('open-day', $a->getSlug('en_GB'));
        $this->assertSame('open-day-2', $b->getSlug('en_GB'));
    }

    public function testExistingSlugsAreKeptAndReserved(): void
    {
        $service = $this->service();
        $kept = (new Event())->setContent('en_GB', 'Open Day', null, 'custom-slug');
        $other = (new Event())->setContent('en_GB', 'Custom Slug');
        $service->fill($kept);
        $service->fill($other);

        $this->assertSame('custom-slug', $kept->getSlug('en_GB'));
        $this->assertSame('custom-slug-2', $other->getSlug('en_GB'));
    }

    public function testSetContentKeepsTheExistingSlugWhenNoneGiven(): void
    {
        $event = (new Event())->setContent('en_GB', 'Open Day', null, 'open-day');
        $event->setContent('en_GB', 'Open Day (updated)');

        $this->assertSame('open-day', $event->getSlug('en_GB'));
    }
}
