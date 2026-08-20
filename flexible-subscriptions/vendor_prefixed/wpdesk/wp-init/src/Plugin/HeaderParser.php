<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin;

interface HeaderParser
{
    /** @return array<string, mixed> */
    public function parse(string $plugin_file): array;
}
