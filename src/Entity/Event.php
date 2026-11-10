<?php

namespace EventManagerKit\Entity;

use DateTimeImmutable;
use IntlDateFormatter;
use InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use EventManagerKit\Translation\{LocaleContext, Translations};

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

    /**
     * Concrete file IDs (file manager) for the event's media.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $imageID = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $videoID = null;

    /**
     * Poster image shown before the video plays.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $videoPosterID = null;

    /**
     * One of LINK_TYPES; the value is a page ID, URL, email address or phone number to match.
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected ?string $linkType = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $linkValue = null;

    public const LINK_TYPES = ['page', 'url', 'email', 'tel'];

    public function __construct()
    {
        parent::__construct();
        $this->contents = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getImageID(): ?int
    {
        return $this->imageID;
    }

    public function getVideoID(): ?int
    {
        return $this->videoID;
    }

    public function getVideoPosterID(): ?int
    {
        return $this->videoPosterID;
    }

    /**
     * 0/null clears. The poster only makes sense with a video, so it is dropped without one.
     */
    public function setMedia(?int $imageID, ?int $videoID, ?int $videoPosterID): static
    {
        $this->imageID = $imageID ?: null;
        $this->videoID = $videoID ?: null;
        $this->videoPosterID = $this->videoID ? ($videoPosterID ?: null) : null;

        return $this;
    }

    public function getLinkType(): ?string
    {
        return $this->linkType;
    }

    public function getLinkValue(): ?string
    {
        return $this->linkValue;
    }

    /**
     * @throws InvalidArgumentException for an unknown type or a value invalid for that type
     */
    public function setLink(?string $type, ?string $value): static
    {
        $value = trim((string) $value);
        if (!$type || $value === '') {
            $this->linkType = $this->linkValue = null;

            return $this;
        }
        if (!in_array($type, self::LINK_TYPES, true) || !self::isValidLink($type, $value)) {
            throw new InvalidArgumentException('Invalid event link.');
        }
        $this->linkType = $type;
        $this->linkValue = $value;

        return $this;
    }

    public static function isValidLink(string $type, string $value): bool
    {
        return match ($type) {
            'page' => ctype_digit($value) && (int) $value > 0,
            'url' => (bool) preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'tel' => (bool) preg_match('/^\+?[0-9 ()\-.]{5,30}$/', $value),
            default => false,
        };
    }

    /**
     * Ready-to-use href, or null when there is no link (or its page is gone).
     */
    public function getLinkHref(): ?string
    {
        return match ($this->linkType) {
            'url' => $this->linkValue,
            'email' => 'mailto:' . $this->linkValue,
            'tel' => 'tel:' . preg_replace('/[^0-9+]/', '', (string) $this->linkValue),
            'page' => ($page = \Concrete\Core\Page\Page::getByID((int) $this->linkValue)) && !$page->isError()
                ? (string) \Concrete\Core\Support\Facade\Url::to($page) : null,
            default => null,
        };
    }
}
