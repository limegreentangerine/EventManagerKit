<?php

namespace EventManagerKit\Entity;

use IntlDateFormatter;
use DateTimeImmutable;
use InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use EventManagerKit\Translation\{LocaleContext, Translations};
use Doctrine\Common\Collections\{ArrayCollection, Collection};

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="emEvents",
 *      indexes={@ORM\Index(name="idx_start_date", columns={"startDate"})}
 * )
 */
class Event extends UpdatedGuidEntity
{
    /**
     * @ORM\OneToMany(targetEntity="EventContent", mappedBy="event", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $contents;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false)
     */
    protected DateTimeImmutable $startDate;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false)
     */
    protected DateTimeImmutable $endDate;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default": 0})
     */
    protected bool $allDay = false;

    /**
     * Draft events are visible in the dashboard only.
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default": 1})
     */
    protected bool $published = true;

    /**
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="locationId", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected ?Location $location = null;

    /**
     * @ORM\ManyToOne(targetEntity="EventType")
     * @ORM\JoinColumn(name="eventTypeId", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected ?EventType $type = null;

    /**
     * @ORM\ManyToMany(targetEntity="EventTag")
     * @ORM\JoinTable(
     *      name="emEventEventTags",
     *      joinColumns={@ORM\JoinColumn(name="eventId", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="eventTagId", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected Collection $tags;

    public function __construct()
    {
        parent::__construct();
        $this->contents = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getContents(): Collection
    {
        return $this->contents;
    }

    /**
     * Content for a locale, falling back to the default locale, then any
     * translation. Null only when the event has no content at all.
     */
    public function getContent(?string $locale = null, ?string $defaultLocale = null): ?EventContent
    {
        return Translations::pick($this->contents, $locale ?? LocaleContext::active(), $defaultLocale ?? LocaleContext::default());
    }

    public function getName(?string $locale = null, ?string $defaultLocale = null): string
    {
        return $this->getContent($locale, $defaultLocale)?->getName() ?? '';
    }

    /**
     * Set (or create) the content for exactly this locale.
     */
    public function setContent(string $locale, string $name, ?string $description = null, ?string $slug = null): static
    {
        foreach ($this->contents as $content) {
            if (strcasecmp($content->getLocale(), $locale) === 0) {
                $content->setName($name)->setDescription($description);
                // Keep the existing slug (links in the wild) unless a new one is given.
                if ($slug !== null) {
                    $content->setSlug($slug);
                }

                return $this;
            }
        }

        $this->contents->add(
            (new EventContent())->setEvent($this)->setLocale($locale)->setName($name)->setDescription($description)->setSlug($slug),
        );

        return $this;
    }

    /**
     * Slug in the displayed locale (falling back like content does), or null
     * when the event has none yet.
     */
    public function getSlug(?string $locale = null): ?string
    {
        return $this->getContent($locale)?->getSlug();
    }

    public function removeContent(EventContent $content): static
    {
        // removeElement (not remove): remove() takes a collection key.
        $this->contents->removeElement($content);

        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * @throws InvalidArgumentException when the end is before the start
     */
    public function setDates(DateTimeImmutable $start, DateTimeImmutable $end): static
    {
        if ($end < $start) {
            throw new InvalidArgumentException('Event end date cannot be before its start date.');
        }

        $this->startDate = $start;
        $this->endDate = $end;

        return $this;
    }

    /**
     * Start/end for dashboard lists, in the displayed locale; time omitted for all-day events.
     */
    public function getStartString(): string
    {
        return $this->formatDate($this->startDate);
    }

    public function getEndString(): string
    {
        return $this->formatDate($this->endDate);
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        $formatter = new IntlDateFormatter(
            LocaleContext::active(),
            IntlDateFormatter::MEDIUM,
            $this->allDay ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT,
        );

        return (string) $formatter->format($date);
    }

    public function getLocationName(): string
    {
        return $this->location ? $this->location->getFullName() : '';
    }

    public function getTypeName(): string
    {
        return $this->type ? $this->type->getName() : '';
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getStatusName(): string
    {
        return $this->published ? t('Published') : t('Draft');
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * True when the event starts and ends on different calendar days.
     */
    public function isMultiDay(): bool
    {
        return $this->startDate->format('Y-m-d') !== $this->endDate->format('Y-m-d');
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getType(): ?EventType
    {
        return $this->type;
    }

    public function setType(?EventType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(EventTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(EventTag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
