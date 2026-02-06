<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
class Iso8601Format implements Format
{
    private \DateTimeImmutable $date;
    public function __construct(\DateTimeInterface $date)
    {
        if ($date instanceof \DateTime) {
            $date = \DateTimeImmutable::createFromMutable($date);
        }
        $this->date = $date;
    }
    public function __toString(): string
    {
        return $this->date->setTimezone(wp_timezone())->format('c');
    }
}
