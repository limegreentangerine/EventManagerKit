<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Concrete's global translation helper isn't loaded outside a full CMS boot.
if (!function_exists('t')) {
    function t(string $text, mixed ...$args): string
    {
        return $args ? vsprintf($text, $args) : $text;
    }
}
