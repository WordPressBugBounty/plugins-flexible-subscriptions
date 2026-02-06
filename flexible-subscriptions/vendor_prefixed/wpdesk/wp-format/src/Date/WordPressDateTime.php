<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
/**
 * Provides a date with time, respecting global WordPress settings.
 */
class WordPressDateTime implements Format
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
        return $this->date->setTimezone(wp_timezone())->format(get_option('date_format') . ' ' . get_option('time_format'));
    }
}
