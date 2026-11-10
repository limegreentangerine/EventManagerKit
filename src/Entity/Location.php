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
}
