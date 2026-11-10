<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Entity\EventTag;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Package\EventManagerKit\Controller\Search\EventTags as SearchController;

class EventTags extends NamedEntityPage
{
    protected function entityClass(): string
    {
        return EventTag::class;
    }

    protected function basePath(): string
    {
        return '/dashboard/event_manager/event_tags';
    }

    protected function searchControllerClass(): string
    {
        return SearchController::class;
    }

    protected function label(): string
    {
        return t('Event Tag');
    }

    protected function pluralLabel(): string
    {
        return t('Event Tags');
    }
}
