<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\Location;
use EventManagerKit\Frontend\LocationMap;

final class LocationMapTest extends TestCase
{
    public function testConfigNeedsCoordinates(): void
    {
        $this->assertNull(LocationMap::config(new Location()));

        $config = LocationMap::config((new Location())->setCoordinates(51.5, -0.12));
        $this->assertSame(51.5, $config['markers'][0]['latitude']);
        $this->assertSame(-0.12, $config['centerLongitude']);
    }

    public function testExternalUrlPrefersCoordinatesThenAddress(): void
    {
        $this->assertNull(LocationMap::externalUrl(new Location()));
        $this->assertStringContainsString('query=10%20Downing%20St', LocationMap::externalUrl((new Location())->setAddress('10 Downing St')));
        $this->assertStringContainsString('query=51.5%2C-0.12', LocationMap::externalUrl((new Location())->setAddress('x')->setCoordinates(51.5, -0.12)));
    }

    public function testCenterFromFeatureSwapsLngLat(): void
    {
        $this->assertSame([51.5, -0.12], LocationMap::centerFromFeature('{"center":[-0.12,51.5]}'));
        $this->assertNull(LocationMap::centerFromFeature('{"center":["a",1]}'));
        $this->assertNull(LocationMap::centerFromFeature('not json'));
    }

    public function testCoordinatesAreValidated(): void
    {
        $location = new Location();
        $this->expectException(\InvalidArgumentException::class);
        $location->setCoordinates(91.0, 0.0);
    }

    public function testOnlineUrlMustBeHttp(): void
    {
        $location = (new Location())->setOnlineUrl(' https://meet.example.com/x ');
        $this->assertSame('https://meet.example.com/x', $location->getOnlineUrl());
        $this->expectException(\InvalidArgumentException::class);
        $location->setOnlineUrl('javascript:alert(1)');
    }
}
