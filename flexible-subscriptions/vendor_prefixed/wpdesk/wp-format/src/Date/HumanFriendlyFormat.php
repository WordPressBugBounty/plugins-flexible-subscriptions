<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date;

use WPDesk\FlexibleSubscriptions\Vendor\Carbon\Carbon;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
/**
 * Display date in a human friendly manner, but only if the difference
 * is less than a month from now.
 */
class HumanFriendlyFormat implements Format
{
    private \DateTimeInterface $date;
    public function __construct(\DateTimeInterface $date)
    {
        if (!class_exists(Carbon::class)) {
            throw new \RuntimeException(sprintf('You cannot use "%s" formatter as Carbon is not installed. Try running "composer require nesbot/carbon".', __CLASS__));
        }
        $this->date = $date;
    }
    public function __toString(): string
    {
        $date = new Carbon($this->date, wp_timezone());
        if ($date->greaterThan(Carbon::now(wp_timezone())) || $date->diffInMonths(Carbon::now(wp_timezone())) > 0) {
            return (string) new DefaultDateFormat($date);
        }
        return $date->locale(get_user_locale())->diffForHumans(['parts' => 1, 'options' => Carbon::FLOOR | Carbon::JUST_NOW, 'skip' => ['m', 's']]);
    }
}
