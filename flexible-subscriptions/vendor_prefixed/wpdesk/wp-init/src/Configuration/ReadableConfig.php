<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration;

/**
 * Allows to read configuration.
 */
interface ReadableConfig
{
    public function get(string $key, $default = null);
    public function has(string $key): bool;
}
