<?php

declare(strict_types=1);

// Minimal stand-ins: Concrete core is not loaded under PHPUnit.

namespace Concrete\Core\Page {
    if (!class_exists(Page::class, false)) {
        class Page
        {
            /** @var array<int, bool> page ID => exists */
            public static array $pages = [];

            public function __construct(private bool $error) {}

            public static function getByID(int $id): self
            {
                return new self(!(self::$pages[$id] ?? false));
            }

            public function isError(): bool
            {
                return $this->error;
            }
        }
    }
}

namespace Concrete\Core\Support\Facade {
    if (!class_exists(Url::class, false)) {
        class Url
        {
            public static function to(mixed $page): string
            {
                return 'https://site.test/page';
            }
        }
    }
}
