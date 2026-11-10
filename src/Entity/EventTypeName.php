<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\GuidEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="emEventTypeNames",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="uniq_parent_locale", columns={"eventTypeId", "locale"})}
 * )
 */
class EventTypeName extends GuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="EventType", inversedBy="names")
     * @ORM\JoinColumn(name="eventTypeId", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected EventType $eventType;

    /**
     * @ORM\Column(type="string", length=16, nullable=false, options={"comment": "Locale, e.g. en_GB"})
     */
    protected string $locale = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $name = '';

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

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function setEventType(EventType $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }
}
