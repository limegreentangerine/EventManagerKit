<?php

declare(strict_types=1);

namespace EventManagerKit\Frontend;

use DateTimeImmutable;
use Doctrine\ORM\{EntityManagerInterface, QueryBuilder};
use EventManagerKit\Entity\{Event, EventContent, EventTag, EventType, Location};

/**
 * Reads the events visitors may see: published only, soonest first.
 */
final class EventFinder
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    private function base(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.published = true')
            ->orderBy('e.startDate', 'ASC')
            ->addOrderBy('e.id', 'ASC');
    }

    private function page(QueryBuilder $qb, int $limit, int $offset): EventPage
    {
        // One extra row tells us whether there is a next page without a COUNT.
        $rows = $qb->setFirstResult($offset)->setMaxResults($limit + 1)->getQuery()->getResult();

        return new EventPage(array_slice($rows, 0, $limit), count($rows) > $limit);
    }

    /**
     * Events that have not finished yet.
     */
    public function upcoming(int $limit, ?DateTimeImmutable $now = null): EventPage
    {
        $qb = $this->base()
            ->andWhere('e.endDate >= :now')
            ->setParameter('now', $now ?? new DateTimeImmutable());

        return $this->page($qb, $limit, 0);
    }

    /**
     * Events overlapping the query's month, filtered, one page at a time.
     */
    public function forMonth(EventQuery $query): EventPage
    {
        $qb = $this->base()
            ->andWhere('e.startDate <= :monthEnd AND e.endDate >= :monthStart')
            ->setParameter('monthStart', $query->monthStart())
            ->setParameter('monthEnd', $query->monthEnd());

        if ($query->typeId) {
            $qb->andWhere('IDENTITY(e.type) = :type')->setParameter('type', $query->typeId);
        }
        if ($query->locationId) {
            // A parent location also matches events held in its child locations.
            $qb->leftJoin('e.location', 'l')
                ->andWhere('l.id = :location OR IDENTITY(l.parent) = :location')
                ->setParameter('location', $query->locationId);
        }
        if ($query->tagId) {
            $qb->innerJoin('e.tags', 't')->andWhere('t.id = :tag')->setParameter('tag', $query->tagId);
        }

        return $this->page($qb, EventQuery::PER_PAGE, ($query->page - 1) * EventQuery::PER_PAGE);
    }

    /**
     * The content row whose slug matches, in any locale. Drafts are only
     * returned when $includeDrafts is set (admin preview).
     */
    public function findBySlug(string $slug, bool $includeDrafts = false): ?EventContent
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(EventContent::class, 'c')
            ->innerJoin('c.event', 'e')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1);
        if (!$includeDrafts) {
            $qb->andWhere('e.published = true');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Options for the filter selects, as id => name in the displayed locale.
     *
     * @return array{types:array<string,string>,locations:array<string,string>,tags:array<string,string>}
     */
    public function filterOptions(): array
    {
        $names = function (string $class, bool $topLevelOnly = false): array {
            $options = [];
            foreach ($this->em->getRepository($class)->findAll() as $entity) {
                if (!$topLevelOnly || !$entity->getParent()) {
                    $options[$entity->getID()] = $entity->getName();
                }
            }
            asort($options);

            return $options;
        };

        return [
            'types' => $names(EventType::class),
            'locations' => $names(Location::class, true),
            'tags' => $names(EventTag::class),
        ];
    }
}
