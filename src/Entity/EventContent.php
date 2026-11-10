<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\GuidEntity;

/**
 * Per-locale text for an Event.
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="emEventContents",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="uniq_event_locale", columns={"eventId", "locale"})}
 * )
 */
class EventContent extends GuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="contents")
     * @ORM\JoinColumn(name="eventId", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Event $event;

    /**
     * @ORM\Column(type="string", length=16, nullable=false, options={"comment": "Locale, e.g. en_GB"})
     */
    protected string $locale = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $name = '';

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $description = null;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        return $this;
    }
}
