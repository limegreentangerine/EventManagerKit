<?php

namespace EventManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class Locations extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column('n.name', t('Name'), 'getName', true));
        $this->addColumn(new Column('l.parent', t('Parent'), 'getParentName', false));
        $this->setDefaultSortColumn($this->getColumnByKey('n.name'), 'asc');
    }
}
