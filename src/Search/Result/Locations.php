<?php

namespace EventManagerKit\Search\Result;

class Locations extends AbstractResult
{
    protected function detailsPath(): string
    {
        return '/dashboard/event_manager/locations/details';
    }
}
