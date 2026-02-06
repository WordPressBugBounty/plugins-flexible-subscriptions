<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;
class EmailSanitizer implements Sanitizer
{
    public function sanitize($value): string
    {
        return sanitize_email($value);
    }
}
