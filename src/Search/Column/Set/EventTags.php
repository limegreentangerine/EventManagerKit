<?php

namespace EventManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class EventTags extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column('n.name', t('Name'), 'getName', true));
        $this->addColumn(new Column('t.handle', t('Handle'), 'getHandle', false));
        $this->setDefaultSortColumn($this->getColumnByKey('n.name'), 'asc');
    }
}
