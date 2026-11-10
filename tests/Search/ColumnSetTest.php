<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Search;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use EventManagerKit\Entity\{Event, EventTag, EventType, Location};
use EventManagerKit\Search\Column\Set\{EventTags, EventTypes, Events, Locations};

final class ColumnSetTest extends TestCase
{
    public static function sets(): array
    {
        return [
            'types' => [EventTypes::class, EventType::class, 'asc'],
            'tags' => [EventTags::class, EventTag::class, 'asc'],
            'locations' => [Locations::class, Location::class, 'asc'],
            'events' => [Events::class, Event::class, 'desc'],
        ];
    }

    /**
     * Every column callback must exist on the entity the list returns, or the
     * dashboard table throws when rendering that column.
     */
    #[DataProvider('sets')]
    public function testColumnCallbacksExistOnTheEntity(string $setClass, string $entityClass, string $direction): void
    {
        $set = new $setClass();

        $this->assertSame($direction, $set->getDefaultSortColumn()->getColumnDefaultSortDirection());
        foreach ($set->getColumns() as $column) {
            $this->assertTrue(
                method_exists($entityClass, $column->getColumnCallback()),
                sprintf('%s has no %s()', $entityClass, $column->getColumnCallback()),
            );
        }
    }
}
