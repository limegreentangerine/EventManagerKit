<?php

namespace EventManagerKit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

/**
 * @ORM\Entity
 * @ORM\Table(name="emEventTypes")
 */
class EventType extends UpdatedGuidEntity
{
    use HasNames;

    /**
     * @ORM\OneToMany(targetEntity="EventTypeName", mappedBy="eventType", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $names;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false, options={"comment": "Unique handle"})
     */
    protected string $handle = '';

    public function __construct()
    {
        parent::__construct();
        $this->names = new ArrayCollection();
    }

    protected function newName(): EventTypeName
    {
        return (new EventTypeName())->setEventType($this);
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
}
