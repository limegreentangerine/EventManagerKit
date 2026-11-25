<?php

namespace EventManagerKit\Search\Result;

class AgeGroups extends AbstractResult
{
    protected function detailsPath(): string
    {
        return '/dashboard/event_manager/age_groups/details';
    }
}
