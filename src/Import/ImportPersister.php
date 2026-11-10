<?php

declare(strict_types=1);

namespace EventManagerKit\Import;

use EventManagerKit\Slug\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Translation\LocaleContext;
use EventManagerKit\Entity\{Event, EventTag, EventType, Location};

/**
 * Applies a validated ImportPlan inside one transaction: all rows or none.
 * Types, tags and locations are matched by a handle derived from the name in
 * the default locale (or the first one given) and created when missing.
 * Existing names are updated for the locales given; others are left alone.
 */
final class ImportPersister
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /**
     * Re-importing the same file must not duplicate events: same start and the
     * same name in the default locale counts as already imported.
     */
    private function exists(ImportRow $row, string $default): bool
    {
        $name = $row->names[$default] ?? reset($row->names);
        $candidates = $this->em->getRepository(Event::class)->findBy(['startDate' => $row->start]);
        foreach ($candidates as $event) {
            if ($event->getName($default, $default) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string<EventType|EventTag|Location> $class
     * @param array<string,string>                      $names
     */
    private function named(string $class, array $names, string $default, ?Location $parent = null): object
    {
        $handle = CsvImporter::handleFor($names[$default] ?? reset($names));
        // A sub location handle is scoped to its parent so "Stage" in two halls stays distinct.
        if ($parent) {
            $handle = $parent->getHandle() . '_' . $handle;
        }

        $entity = $this->em->getRepository($class)->findOneBy(['handle' => $handle]);
        if (!$entity) {
            $entity = (new $class())->setHandle($handle);
            if ($parent) {
                $entity->setParent($parent);
            }
            $this->em->persist($entity);
            // Flush so later rows in this import find it by handle.
            $this->em->flush();
        }

        foreach ($names as $locale => $name) {
            $entity->setName($locale, $name);
        }

        return $entity;
    }

    /**
     * @return array{created:int,skipped:int}
     */
    public function persist(ImportPlan $plan): array
    {
        $created = $skipped = 0;
        $default = LocaleContext::default();
        $slugs = SlugService::forEntityManager($this->em);

        $this->em->wrapInTransaction(function () use ($plan, $default, $slugs, &$created, &$skipped) {
            foreach ($plan->rows as $row) {
                if ($this->exists($row, $default)) {
                    $skipped++;
                    continue;
                }

                $event = (new Event())->setDates($row->start, $row->end)->setAllDay($row->allDay);
                foreach ($row->names as $locale => $name) {
                    $event->setContent($locale, $name, $row->descriptions[$locale] ?? null);
                }

                $slugs->fill($event);

                $parent = $row->location ? $this->named(Location::class, $row->location, $default) : null;
                $event->setLocation($row->subLocation ? $this->named(Location::class, $row->subLocation, $default, $parent) : $parent);
                $event->setType($row->type ? $this->named(EventType::class, $row->type, $default) : null);
                foreach ($row->tags as $tag) {
                    $event->addTag($this->named(EventTag::class, $tag, $default));
                }

                $this->em->persist($event);
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }
}
