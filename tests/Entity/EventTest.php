<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\{Event, EventTag, EventType, Location};

final class EventTest extends TestCase
{
    public function testFeaturedIsOffByDefaultAndSettable(): void
    {
        $event = new Event();

        $this->assertFalse($event->isFeatured());
        $this->assertTrue($event->setFeatured(true)->isFeatured());
    }

    public function testContentFallsBackToDefaultLocale(): void
    {
        $event = (new Event())->setContent('en_GB', 'Open day', 'Come along');

        $this->assertSame('Open day', $event->getName('cy_GB'));
        $this->assertSame('Come along', $event->getContent('cy_GB')->getDescription());
    }

    public function testNameIsEmptyWithoutAnyContent(): void
    {
        $this->assertSame('', (new Event())->getName('cy_GB'));
        $this->assertNull((new Event())->getContent('cy_GB'));
    }

    public function testSetContentUpdatesExistingLocaleInsteadOfDuplicating(): void
    {
        $event = (new Event())->setContent('en_GB', 'One')->setContent('en_GB', 'Two')->setContent('cy_GB', 'Dau');

        $this->assertCount(2, $event->getContents());
        $this->assertSame('Two', $event->getName('en_GB'));
        $this->assertSame('Dau', $event->getName('cy_GB'));
    }

    public function testRemoveContentActuallyRemoves(): void
    {
        $event = (new Event())->setContent('en_GB', 'One')->setContent('cy_GB', 'Un');
        $event->removeContent($event->getContent('cy_GB'));

        $this->assertCount(1, $event->getContents());
        $this->assertSame('One', $event->getName('cy_GB'));
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Event())->setDates(new DateTimeImmutable('2026-05-02'), new DateTimeImmutable('2026-05-01'));
    }

    public function testMultiDay(): void
    {
        $event = (new Event())->setDates(new DateTimeImmutable('2026-05-01 10:00'), new DateTimeImmutable('2026-05-01 12:00'));
        $this->assertFalse($event->isMultiDay());

        $event->setDates(new DateTimeImmutable('2026-05-01 10:00'), new DateTimeImmutable('2026-05-02 09:00'));
        $this->assertTrue($event->isMultiDay());
    }

    public function testTagsAreNotDuplicated(): void
    {
        $tag = new EventTag();
        $event = (new Event())->addTag($tag)->addTag($tag);
        $this->assertCount(1, $event->getTags());

        $event->removeTag($tag);
        $this->assertCount(0, $event->getTags());
    }

    public function testLocationAndTypeDefaultToNull(): void
    {
        $event = new Event();
        $this->assertNull($event->getLocation());
        $this->assertNull($event->getType());
        $this->assertFalse($event->isAllDay());

        $event->setLocation(new Location());
        $this->assertInstanceOf(Location::class, $event->getLocation());
    }

    public function testListStringsAndNames(): void
    {
        $type = (new EventType())->setName('en_GB', 'Workshop');
        $hall = (new Location())->setName('en_GB', 'Hall');
        $event = (new Event())
            ->setDates(new DateTimeImmutable('2026-05-15 10:00'), new DateTimeImmutable('2026-05-15 12:00'))
            ->setType($type)
            ->setLocation($hall);

        $this->assertSame('Workshop', $event->getTypeName());
        $this->assertSame('Hall', $event->getLocationName());
        $this->assertStringContainsString('10:00', $event->getStartString());

        $event->setAllDay(true);
        $this->assertStringNotContainsString('10:00', $event->getStartString());
        $this->assertSame('', (new Event())->getTypeName());
    }

    public function testPublishedByDefaultAndCanBeHidden(): void
    {
        $event = new Event();
        $this->assertTrue($event->isPublished());
        $this->assertSame('Published', $event->getStatusName());

        $event->setPublished(false);
        $this->assertSame('Draft', $event->getStatusName());
    }

    public function testPosterIsDroppedWithoutVideo(): void
    {
        $event = (new Event())->setMedia(1, 2, 3);
        $this->assertSame([1, 2, 3], [$event->getImageID(), $event->getVideoID(), $event->getVideoPosterID()]);

        $event->setMedia(1, null, 3);
        $this->assertNull($event->getVideoPosterID());
    }

    public function testLinkHrefPerType(): void
    {
        $event = new Event();
        $this->assertNull($event->getLinkHref());
        $this->assertSame('https://x.test/t', $event->setLink('url', 'https://x.test/t')->getLinkHref());
        $this->assertSame('mailto:a@b.co', $event->setLink('email', 'a@b.co')->getLinkHref());
        $this->assertSame('tel:+441234567890', $event->setLink('tel', '+44 1234 567890')->getLinkHref());
        $this->assertNull($event->setLink(null, '')->getLinkType());
    }

    public function testPageLinkHrefIsNullWhenPageDeleted(): void
    {
        require_once __DIR__ . '/../Stubs/ConcretePageStubs.php';
        \Concrete\Core\Page\Page::$pages = [5 => true];

        $event = new Event();
        $this->assertSame('https://site.test/page', $event->setLink('page', '5')->getLinkHref());
        $this->assertNull($event->setLink('page', '99')->getLinkHref());
    }

    public function testInvalidLinkIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Event())->setLink('url', 'javascript:alert(1)');
    }
}
