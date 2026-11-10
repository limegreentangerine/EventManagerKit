<?php

namespace EventManagerKit\Search\Result;

class Events extends AbstractResult
{
    protected function detailsPath(): string
    {
        return '/dashboard/event_manager/events/details';
    }
}
