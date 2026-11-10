<?php

namespace EventManagerKit\Search\Result;

class EventTags extends AbstractResult
{
    protected function detailsPath(): string
    {
        return '/dashboard/event_manager/event_tags/details';
    }
}
