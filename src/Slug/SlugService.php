<?php

declare(strict_types=1);

namespace EventManagerKit\Slug;

use Closure;
use EventManagerKit\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Entity\EventContent;

/**
 * Gives each event content a slug that is unique within its locale.
 *
 * Slugs handed out in this service's lifetime are remembered, because rows
 * persisted but not yet flushed (a CSV import) are invisible to the database
 * check and would otherwise collide.
 */
final class SlugService
{
    /** @var array<string,true> "locale|slug" */
    private array $reserved = [];

    /**
     * @param Closure(string,string,?Event):bool $takenInDatabase locale, slug, event to ignore
     */
    public function __construct(private readonly Closure $takenInDatabase) {}

    public static function forEntityManager(EntityManagerInterface $em): self
    {
        return new self(function (string $locale, string $slug, ?Event $except) use ($em): bool {
            $qb = $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(EventContent::class, 'c')
                ->where('c.locale = :locale AND c.slug = :slug')
                ->setParameter('locale', $locale)
                ->setParameter('slug', $slug);
            // A brand-new event has no row yet, so there is nothing to exclude.
            if ($except && $em->contains($except)) {
                $qb->andWhere('c.event != :event')->setParameter('event', $except);
            }

            return (int) $qb->getQuery()->getSingleScalarResult() > 0;
        });
    }

    public function isTaken(string $locale, string $slug, ?Event $except = null): bool
    {
        return isset($this->reserved[$locale . '|' . $slug]) || ($this->takenInDatabase)($locale, $slug, $except);
    }

    /**
     * Fill every content row that has no slug yet, from its name.
     */
    public function fill(Event $event): void
    {
        foreach ($event->getContents() as $content) {
            if ($content->getSlug() === null || $content->getSlug() === '') {
                $locale = $content->getLocale();
                $slug = SlugGenerator::unique(
                    SlugGenerator::slugify($content->getName()),
                    fn(string $candidate) => $this->isTaken($locale, $candidate, $event),
                );
                $this->reserve($locale, $slug);
                $content->setSlug($slug);
            } else {
                $this->reserve($content->getLocale(), $content->getSlug());
            }
        }
    }

    private function reserve(string $locale, string $slug): void
    {
        $this->reserved[$locale . '|' . $slug] = true;
    }
}
