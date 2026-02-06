<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval;

final class NullSpec implements IntervalSpec
{
    private const NULL_SPEC = 'PT0S';
    public function __toString(): string
    {
        return self::NULL_SPEC;
    }
}
