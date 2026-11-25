<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Activities;
use EventManagerKit\Entity\AgeGroup;
use EventManagerKit\Entity\Location;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Package\EventManagerKit\Controller\Search\AgeGroups as SearchController;

class AgeGroups extends NamedEntityPage
{
    private int $sortOrder = 0;

    protected function entityClass(): string
    {
        return AgeGroup::class;
    }

    protected function basePath(): string
    {
        return Activities::AGE_GROUPS_PATH;
    }

    protected function searchControllerClass(): string
    {
        return SearchController::class;
    }

    protected function label(): string
    {
        return t('Age Group');
    }

    protected function pluralLabel(): string
    {
        return t('Age Groups');
    }

    protected function prepareForm($entity = null): void
    {
        $this->set('sortOrder', (string) ($this->request->request->get('sortOrder') ?? $entity?->getSortOrder() ?? 0));
    }

    protected function validateExtra(array $post): void
    {
        $value = trim((string) ($post['sortOrder'] ?? '0'));
        if (!preg_match('/^-?\d{1,6}$/', $value === '' ? '0' : $value)) {
            $this->error->add(t('The order must be a whole number.'));
        }
        $this->sortOrder = (int) $value;
    }

    protected function applyExtra($entity, ?Location $parent): void
    {
        $entity->setSortOrder($this->sortOrder);
    }
}
