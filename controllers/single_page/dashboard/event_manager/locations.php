<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use EventManagerKit\Entity\Location;
use EventManagerKit\Frontend\LocationMap;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Package\EventManagerKit\Controller\Search\Locations as SearchController;

class Locations extends NamedEntityPage
{
    /** @var array{address:string,latitude:?float,longitude:?float,onlineUrl:string} */
    private array $place = ['address' => '', 'latitude' => null, 'longitude' => null, 'onlineUrl' => ''];
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

    private static function number(mixed $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (is_numeric($value) ? (float) $value : NAN);
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
        $posted = $this->request->request->all();
        $this->set('place', [
            'address' => (string) ($posted['address'] ?? $entity?->getAddress()),
            'latitude' => (string) ($posted['latitude'] ?? $entity?->getLatitude()),
            'longitude' => (string) ($posted['longitude'] ?? $entity?->getLongitude()),
            'onlineUrl' => (string) ($posted['onlineUrl'] ?? $entity?->getOnlineUrl()),
        ]);

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

    protected function validateExtra(array $post): void
    {
        $lat = self::number($post['latitude'] ?? '');
        $lng = self::number($post['longitude'] ?? '');
        $probe = new Location();
        try {
            $probe->setCoordinates(is_nan((float) $lat) ? 1000.0 : $lat, is_nan((float) $lng) ? 1000.0 : $lng);
        } catch (\InvalidArgumentException) {
            $this->error->add(t('Latitude and longitude must both be set, latitude between -90 and 90 and longitude between -180 and 180.'));
        }
        try {
            $probe->setOnlineUrl((string) ($post['onlineUrl'] ?? ''));
        } catch (\InvalidArgumentException) {
            $this->error->add(t('The online event URL must start with http:// or https://.'));
        }
        $this->place = ['address' => trim((string) ($post['address'] ?? '')), 'latitude' => $lat, 'longitude' => $lng, 'onlineUrl' => (string) ($post['onlineUrl'] ?? '')];
    }

    protected function optionLabel($entity): string
    {
        return $entity->getFullName();
    }

    protected function applyExtra($entity, ?Location $parent): void
    {
        $entity->setParent($parent)->setAddress($this->place['address'])->setOnlineUrl($this->place['onlineUrl']);
        $lat = $this->place['latitude'];
        $lng = $this->place['longitude'];
        // Blank coordinates plus an address: look them up (MapboxKit, if installed). Clear them to look up again.
        if ($lat === null && $lng === null && $this->place['address'] !== '' && ($found = LocationMap::geocode($this->place['address']))) {
            [$lat, $lng] = $found;
        }
        $entity->setCoordinates($lat, $lng);
    }
}
