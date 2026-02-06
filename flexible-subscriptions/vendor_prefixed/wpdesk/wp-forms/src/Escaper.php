<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms;

interface Escaper
{
    /** @param mixed $value */
    public function escape($value): string;
}
