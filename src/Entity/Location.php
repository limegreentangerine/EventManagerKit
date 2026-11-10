<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use EventManagerKit\Translation\LocaleContext;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

/**
 * @ORM\Entity
 * @ORM\Table(name="emLocations")
 */
class Location extends UpdatedGuidEntity
{
    use HasNames;

    /**
     * @ORM\OneToMany(targetEntity="LocationName", mappedBy="location", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $names;

    /**
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected ?Location $parent = null;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Unique handle"})
     */
    protected string $handle = '';

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    protected ?string $address = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected ?float $latitude = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected ?float $longitude = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $onlineUrl = null;

    public function __construct()
    {
        parent::__construct();
        $this->names = new ArrayCollection();
    }

    protected function newName(): LocationName
    {
        return (new LocationName())->setLocation($this);
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): static
    {
        $this->handle = $handle;

        return $this;
    }

    public function getParent(): ?Location
    {
        return $this->parent;
    }

    public function getParentName(): string
    {
        return $this->parent ? $this->parent->getName() : '';
    }

    public function setParent(?Location $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * "Parent > Child" label for dashboard lists and filters.
     */
    public function getFullName(?string $locale = null): string
    {
        $locale ??= LocaleContext::active();
        $name = $this->getName($locale);

        return $this->parent ? t('%s > %s', $this->parent->getName($locale), $name) : $name;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $address = trim((string) $address);
        $this->address = $address === '' ? null : $address;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Both or neither; null clears them.
     *
     * @throws \InvalidArgumentException for one without the other, or out of range
     */
    public function setCoordinates(?float $latitude, ?float $longitude): static
    {
        if ($latitude === null && $longitude === null) {
            $this->latitude = $this->longitude = null;

            return $this;
        }
        if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('Invalid coordinates.');
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

    public function getOnlineUrl(): ?string
    {
        return $this->onlineUrl;
    }

    /**
     * @throws \InvalidArgumentException unless blank or an http(s) URL
     */
    public function setOnlineUrl(?string $url): static
    {
        $url = trim((string) $url);
        if ($url !== '' && !Event::isValidLink('url', $url)) {
            throw new \InvalidArgumentException('Invalid online event URL.');
        }
        $this->onlineUrl = $url === '' ? null : $url;

        return $this;
    }
}
