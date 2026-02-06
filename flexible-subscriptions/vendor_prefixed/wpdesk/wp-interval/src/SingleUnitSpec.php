<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval;

/**
 * Interval consiting only of one unit and length, like 3 days, or 1 week.
 */
class SingleUnitSpec implements IntervalSpec
{
    /** @var int<0, max> */
    private int $length;
    private string $unit;
    /**
     * @param int<0, max>    $length
     * @param string $unit Unit of the interval abbreviated. E.g. 'D' for days.
     */
    public function __construct(int $length, string $unit)
    {
        $this->length = $length;
        $this->unit = strtoupper($unit);
    }
    public function get_unit(): string
    {
        return $this->unit;
    }
    public function __toString(): string
    {
        return "P{$this->length}{$this->unit}";
    }
}
