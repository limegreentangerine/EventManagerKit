<?php

namespace EventManagerKit\Search\Result\Item;

use Concrete\Core\Search\Column\Set;
use Concrete\Core\Support\Facade\Url as URL;
use Concrete\Core\Search\Result\{Item, Result};

/**
 * A search result row linking to /<detailsPath>/<id>.
 */
class Entry extends Item
{
    protected object $entity;

    public function __construct(Result $result, Set $columns, object $item, protected string $detailsPath)
    {
        parent::__construct($result, $columns, $item);
        $this->entity = $item;
    }

    public function getID()
    {
        return $this->entity->getID();
    }

    public function getViewUrl()
    {
        return URL::to($this->detailsPath, $this->entity->getID());
    }
}
