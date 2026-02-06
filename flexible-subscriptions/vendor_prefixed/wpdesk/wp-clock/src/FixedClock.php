<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Clock;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
class FixedClock implements ClockInterface
{
    private \DateTimeImmutable $date;
    /**
     * @param \DateTimeImmutable|string $date
     */
    public function __construct($date)
    {
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }
        $this->date = $date;
    }
    public function now(): \DateTimeImmutable
    {
        return $this->date;
    }
}
