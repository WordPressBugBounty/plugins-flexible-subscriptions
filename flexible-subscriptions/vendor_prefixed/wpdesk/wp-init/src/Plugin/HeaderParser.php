<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin;

interface HeaderParser
{
    public function parse(string $plugin_file): array;
}
