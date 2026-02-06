<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval;

final class StringSpec implements IntervalSpec
{
    private string $spec;
    public function __construct(string $spec)
    {
        $spec = strtoupper($spec);
        if (strpos($spec, 'P') !== 0) {
            throw new \RuntimeException('Specification string is not a valid ISO-8601 format.');
        }
        $this->spec = $spec;
    }
    public function __toString(): string
    {
        return empty($this->spec) ? (string) new NullSpec() : $this->spec;
    }
}
