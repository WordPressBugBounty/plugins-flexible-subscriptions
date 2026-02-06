<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Clock;

use DateTimeImmutable;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
/**
 * Standard clock, always returning time at the moment of the call, respecting WordPress time zone.
 */
class WpClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', wp_timezone());
    }
}
