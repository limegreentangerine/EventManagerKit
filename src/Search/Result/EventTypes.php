<?php

namespace EventManagerKit\Search\Result;

class EventTypes extends AbstractResult
{
    protected function detailsPath(): string
    {
        return '/dashboard/event_manager/event_types/details';
    }
}
