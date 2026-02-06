<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval;

use WPDesk\FlexibleSubscriptions\Vendor\Carbon\CarbonInterval;
class WPInterval extends CarbonInterval
{
    private ?string $preferred_unit = null;
    private ?string $original_spec = null;
    public function __construct($years = null, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null, $microseconds = null)
    {
        if (is_string($years)) {
            $this->original_spec = strtoupper($years);
        }
        $preferred_unit = self::detect_preferred_unit_from_constructor_args($years, $months, $weeks, $days, $hours, $minutes);
        if ($this->original_spec === null && $preferred_unit !== null) {
            $this->original_spec = self::build_single_unit_spec_from_args($preferred_unit, $years, $months, $weeks, $days, $hours, $minutes);
        }
        parent::__construct($years, $months, $weeks, $days, $hours, $minutes, $seconds, $microseconds);
        if ($preferred_unit !== null) {
            $this->preferred_unit = $preferred_unit;
        }
        if ($this->original_spec !== null) {
            $this->original_spec = strtoupper($this->original_spec);
        }
    }
    /** @return static */
    public static function from_spec(IntervalSpec $spec): self
    {
        // @phpstan-ignore new.static
        return new static((string) $spec);
    }
    public function length(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }
        $raw_values = $this->get_raw_values();
        if ($this->preferred_unit !== null) {
            $map = ['Y' => 'years', 'M' => 'months', 'W' => 'weeks', 'D' => 'days'];
            $key = $map[$this->preferred_unit] ?? null;
            if ($key !== null && array_key_exists($key, $raw_values)) {
                return $raw_values[$key];
            }
        }
        if (count($raw_values) === 1) {
            return current($raw_values);
        }
        if (array_key_exists('days', $raw_values)) {
            return $raw_values['days'];
        }
        return array_values($raw_values)[0];
    }
    /** @return array<string, int> */
    private function get_raw_values(): array
    {
        return array_filter(
            ['years' => $this->years, 'months' => $this->months, 'weeks' => $this->weeks, 'days' => $this->dayz],
            // @phpstan-ignore argument.type
            'intval'
        );
    }
    public function unit(): string
    {
        if ($this->isEmpty()) {
            return 'M';
        }
        if ($this->preferred_unit !== null) {
            return $this->preferred_unit;
        }
        $raw_values = array_keys($this->get_raw_values());
        $raw_values = array_map(static fn($unit) => strtoupper(substr($unit, 0, 1)), $raw_values);
        if (in_array('D', $raw_values, \true)) {
            return 'D';
        }
        return $raw_values[0];
    }
    public function __toString(): string
    {
        return $this->spec();
    }
    public function spec(bool $microseconds = \false): string
    {
        if (!$microseconds && $this->original_spec !== null) {
            return $this->original_spec;
        }
        return parent::spec($microseconds);
    }
    private static function detect_single_unit_from_string_spec(string $spec_string): ?string
    {
        $spec_string = strtoupper($spec_string);
        if (preg_match('/^P\d+([YMWD])$/', $spec_string, $matches) === 1) {
            return $matches[1];
        }
        return null;
    }
    /**
     * @param string            $preferred_unit
     * @param string|int|float|null $years
     * @param int|float|null        $months
     * @param int|float|null        $weeks
     * @param int|float|null        $days
     * @param int|float|null        $hours
     * @param int|float|null        $minutes
     */
    private static function build_single_unit_spec_from_args(string $preferred_unit, $years, $months, $weeks, $days, $hours, $minutes): ?string
    {
        $units = ['Y' => $years, 'M' => $months, 'W' => $weeks, 'D' => $days, 'H' => $hours, 'I' => $minutes];
        if (!array_key_exists($preferred_unit, $units)) {
            return null;
        }
        $value = $units[$preferred_unit];
        if (!is_numeric($value)) {
            return null;
        }
        $value = (int) $value;
        if ($value === 0) {
            return null;
        }
        if (in_array($preferred_unit, ['Y', 'M', 'W', 'D'], \true)) {
            return sprintf('P%d%s', $value, $preferred_unit);
        }
        if (in_array($preferred_unit, ['H', 'I'], \true)) {
            $designator = $preferred_unit === 'I' ? 'M' : 'H';
            return sprintf('PT%d%s', $value, $designator);
        }
        return null;
    }
    /**
     * @param string|int|float|null $years
     * @param int|float|null        $months
     * @param int|float|null        $weeks
     * @param int|float|null        $days
     * @param int|float|null        $hours
     * @param int|float|null        $minutes
     */
    private static function detect_preferred_unit_from_constructor_args($years, $months, $weeks, $days, $hours, $minutes): ?string
    {
        if (is_string($years)) {
            return self::detect_single_unit_from_string_spec($years);
        }
        $units = ['Y' => $years, 'M' => $months, 'W' => $weeks, 'D' => $days, 'H' => $hours, 'I' => $minutes];
        $non_zero_units = array_filter($units, static fn($value) => is_numeric($value) && (float) $value !== 0.0);
        if (count($non_zero_units) === 1) {
            return array_key_first($non_zero_units);
        }
        return null;
    }
    public function to_readable_string(): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        $length = $this->length();
        $unit = $this->unit();
        if ($length === 1) {
            switch ($unit) {
                case 'D':
                    return __('day', 'flexible-subscriptions');
                case 'W':
                    return __('week', 'flexible-subscriptions');
                case 'M':
                    return __('month', 'flexible-subscriptions');
                case 'Y':
                    return __('year', 'flexible-subscriptions');
                case 'H':
                    return __('hour', 'flexible-subscriptions');
                case 'I':
                    return __('minute', 'flexible-subscriptions');
                default:
                    return trim(sprintf('%d %s', $length, strtolower($unit)));
            }
        }
        switch ($unit) {
            case 'D':
                /* translators: %d: number of days. */
                return sprintf(_n('%d day', '%d days', $length, 'flexible-subscriptions'), $length);
            case 'W':
                /* translators: %d: number of weeks. */
                return sprintf(_n('%d week', '%d weeks', $length, 'flexible-subscriptions'), $length);
            case 'M':
                /* translators: %d: number of months. */
                return sprintf(_n('%d month', '%d months', $length, 'flexible-subscriptions'), $length);
            case 'Y':
                /* translators: %d: number of years. */
                return sprintf(_n('%d year', '%d years', $length, 'flexible-subscriptions'), $length);
            case 'H':
                /* translators: %d: number of hours. */
                return sprintf(_n('%d hour', '%d hours', $length, 'flexible-subscriptions'), $length);
            case 'I':
                /* translators: %d: number of minutes. */
                return sprintf(_n('%d minute', '%d minutes', $length, 'flexible-subscriptions'), $length);
            default:
                return trim(sprintf('%d %s', $length, strtolower($unit)));
        }
    }
}
