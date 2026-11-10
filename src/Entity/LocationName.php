<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\GuidEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="emLocationNames",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="uniq_parent_locale", columns={"locationId", "locale"})}
 * )
 */
class LocationName extends GuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="names")
     * @ORM\JoinColumn(name="locationId", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Location $location;

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

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): static
    {
        $this->location = $location;

        return $this;
    }
}
