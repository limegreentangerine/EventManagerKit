<?php

namespace EventManagerKit\Entity;

use EventManagerKit\Translation\{LocaleContext, Translations};

/**
 * Shared behaviour for entities with a per-locale `names` collection.
 * Using classes define `$names` and newName().
 */
trait HasNames
{
    /**
     * Name in the given locale, falling back to the default locale, then any
     * translation, so a missing translation never renders blank.
     */
    public function getName(?string $locale = null, ?string $defaultLocale = null): string
    {
        $row = Translations::pick($this->names, $locale ?? LocaleContext::active(), $defaultLocale ?? LocaleContext::default());

        return $row ? $row->getName() : '';
    }

    /**
     * Set (or create) the name for exactly this locale.
     */
    public function setName(string $locale, string $name): static
    {
        foreach ($this->names as $row) {
            if (strcasecmp($row->getLocale(), $locale) === 0) {
                $row->setName($name);

                return $this;
            }
        }

        $this->names->add($this->newName()->setLocale($locale)->setName($name));

        return $this;
    }

    public function getNames()
    {
        return $this->names;
    }
}
