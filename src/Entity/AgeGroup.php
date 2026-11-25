<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

/**
 * Who an activity is for, e.g. "Under 5s". Only used when activities are enabled.
 *
 * @ORM\Entity
 * @ORM\Table(name="emAgeGroups")
 */
class AgeGroup extends UpdatedGuidEntity
{
    use HasNames;

    /**
     * @ORM\OneToMany(targetEntity="AgeGroupName", mappedBy="ageGroup", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $names;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Unique handle"})
     */
    protected string $handle = '';

    /**
     * Ages do not sort alphabetically ("5-11" before "Under 5"), so they carry an explicit order.
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    protected int $sortOrder = 0;

    public function __construct()
    {
        parent::__construct();
        $this->names = new ArrayCollection();
    }

    protected function newName(): AgeGroupName
    {
        return (new AgeGroupName())->setAgeGroup($this);
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
