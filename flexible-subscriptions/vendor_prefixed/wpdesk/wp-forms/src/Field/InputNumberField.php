<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer\TextFieldSanitizer;
class InputNumberField extends BasicField
{
    public function get_type(): string
    {
        return 'number';
    }
    public function get_sanitizer(): Sanitizer
    {
        return new TextFieldSanitizer();
    }
    public function get_template_name(): string
    {
        return 'input-number';
    }
}
