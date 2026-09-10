<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Clock;

use DateTimeImmutable;
use DateTimeZone;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
/** Current time in UTC, independent of the configured WordPress timezone. */
final class UtcClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
