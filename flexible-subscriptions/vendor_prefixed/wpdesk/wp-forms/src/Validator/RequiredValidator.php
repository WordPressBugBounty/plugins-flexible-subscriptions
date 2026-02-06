<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Validator;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Validator;
class RequiredValidator implements Validator
{
    public function is_valid($value): bool
    {
        return $value !== null;
    }
    public function get_messages(): array
    {
        return [];
    }
}
