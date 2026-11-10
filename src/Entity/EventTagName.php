<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\GuidEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="emEventTagNames",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="uniq_parent_locale", columns={"eventTagId", "locale"})}
 * )
 */
class EventTagName extends GuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="EventTag", inversedBy="names")
     * @ORM\JoinColumn(name="eventTagId", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected EventTag $eventTag;

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

    public function getEventTag(): EventTag
    {
        return $this->eventTag;
    }

    public function setEventTag(EventTag $eventTag): static
    {
        $this->eventTag = $eventTag;

        return $this;
    }
}
