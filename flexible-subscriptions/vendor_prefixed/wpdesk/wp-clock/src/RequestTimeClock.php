<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Clock;

use DateTimeImmutable;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
/**
 * Return consistent time whenever called across the entire request lifecycle.
 */
class RequestTimeClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        $request_time = isset($_SERVER['REQUEST_TIME']) ? absint($_SERVER['REQUEST_TIME']) : 0;
        return (new DateTimeImmutable("@{$request_time}"))->setTimezone(wp_timezone());
    }
}
