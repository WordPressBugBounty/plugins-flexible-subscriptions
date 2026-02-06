<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;
class NoSanitize implements Sanitizer
{
    public function sanitize($value)
    {
        return $value;
    }
}
