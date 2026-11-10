<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Entity\Location;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Package\EventManagerKit\Controller\Search\Locations as SearchController;

class Locations extends NamedEntityPage
{
    /** Walk up from $candidate: if we meet $entity, saving would create a loop. */
    private function isSelfOrDescendant(Location $candidate, Location $entity): bool
    {
        for ($node = $candidate; $node; $node = $node->getParent()) {
            if ($node->getID() === $entity->getID()) {
                return true;
            }
        }

        return false;
    }
    protected function entityClass(): string
    {
        return Location::class;
    }

    protected function basePath(): string
    {
        return '/dashboard/event_manager/locations';
    }

    protected function searchControllerClass(): string
    {
        return SearchController::class;
    }

    protected function label(): string
    {
        return t('Location');
    }

    protected function pluralLabel(): string
    {
        return t('Locations');
    }

    protected function prepareForm($entity = null): void
    {
        // Any other location can be a parent, except the location itself.
        $parents = [];
        foreach ($this->em->getRepository(Location::class)->findAll() as $location) {
            if (!$entity || $location->getID() !== $entity->getID()) {
                $parents[$location->getID()] = $location->getFullName();
            }
        }
        asort($parents);
        $this->set('parents', ['' => t('(none)')] + $parents);
    }

    protected function resolveParent(array $post, $entity): ?Location
    {
        $id = (string) ($post['parent'] ?? '');
        if ($id === '') {
            return null;
        }
        $parent = $this->em->find(Location::class, $id);
        if (!$parent) {
            $this->error->add(t('Unable to find the selected parent location.'));
        } elseif ($entity && $this->isSelfOrDescendant($parent, $entity)) {
            $this->error->add(t('A location cannot be its own parent.'));
        }

        return $parent ?: null;
    }

    protected function applyExtra($entity, ?Location $parent): void
    {
        $entity->setParent($parent);
    }
}
