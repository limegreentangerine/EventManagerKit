<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\{AgeGroup, Event, EventTag, EventType, Location};

final class NamedEntitiesTest extends TestCase
{
    public function testTypeNamesPerLocaleWithFallback(): void
    {
        $type = (new EventType())->setHandle('workshop')->setName('en_GB', 'Workshop')->setName('cy_GB', 'Gweithdy');

        $this->assertSame('workshop', $type->getHandle());
        $this->assertSame('Gweithdy', $type->getName('cy_GB'));
        $this->assertSame('Workshop', $type->getName('fr_FR'));
        $this->assertSame($type, $type->getNames()->first()->getEventType());
    }

    public function testSetNameReplacesRatherThanDuplicates(): void
    {
        $tag = (new EventTag())->setName('en_GB', 'Year 1')->setName('en_GB', 'Year 2');

        $this->assertCount(1, $tag->getNames());
        $this->assertSame('Year 2', $tag->getName('en_GB'));
    }

    public function testLocationFullNameIncludesParentInLocale(): void
    {
        $hall = (new Location())->setName('en_GB', 'Main hall')->setName('cy_GB', 'Neuadd');
        $stage = (new Location())->setName('en_GB', 'Stage')->setParent($hall);

        $this->assertSame('Main hall > Stage', $stage->getFullName('en_GB'));
        // Child has no Welsh name: it is blank-safe via fallback, parent uses cy.
        $this->assertSame('Neuadd > Stage', $stage->getFullName('cy_GB'));
        $this->assertSame('Main hall', $hall->getFullName('en_GB'));
    }

    public function testEventAgeGroupsFollowSortOrderWithoutDuplicates(): void
    {
        $adults = (new AgeGroup())->setName('en_GB', 'Adults')->setSortOrder(3);
        $under5 = (new AgeGroup())->setName('en_GB', 'Under 5s')->setSortOrder(1);
        $kids = (new AgeGroup())->setName('en_GB', '5-11')->setName('cy_GB', '5-11 oed')->setSortOrder(2);

        $event = (new Event())->setAgeGroups([$adults, $under5, $kids, $under5]);

        $this->assertSame([$under5, $kids, $adults], $event->getAgeGroups());
        $this->assertSame('5-11 oed', $kids->getName('cy_GB'));
        $this->assertSame($kids, $kids->getNames()->first()->getAgeGroup());
        $this->assertSame([$adults], $event->setAgeGroups([$adults])->getAgeGroups());
    }
}
