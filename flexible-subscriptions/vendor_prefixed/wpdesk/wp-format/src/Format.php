<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format;

/**
 * Generic format interface which indicates that class can be converted to a string with some
 * specific formatting applied.
 */
interface Format
{
    public function __toString(): string;
}
