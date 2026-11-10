<?php

namespace EventManagerKit\Search\Result;

use ClassKit\Search\Result as SearchResult;
use EventManagerKit\Search\Result\Item\Entry;

abstract class AbstractResult extends SearchResult
{
    /** Dashboard path of the details page, e.g. /dashboard/event_manager/events/details */
    abstract protected function detailsPath(): string;

    public function getItemDetails($result)
    {
        return new Entry($this, $this->listColumns, $result, $this->detailsPath());
    }
}
