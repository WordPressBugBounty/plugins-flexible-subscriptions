<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
/**
 * Formats a date (without time) according to global WordPress settings.
 */
class DefaultDateFormat implements Format
{
    private \DateTimeInterface $date;
    public function __construct(\DateTimeInterface $date)
    {
        $this->date = $date;
    }
    public function __toString(): string
    {
        return wp_date(get_option('date_format'), $this->date->getTimestamp());
    }
}
