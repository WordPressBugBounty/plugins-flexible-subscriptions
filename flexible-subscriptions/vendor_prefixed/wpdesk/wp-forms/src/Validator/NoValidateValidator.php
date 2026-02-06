<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Validator;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Validator;
class NoValidateValidator implements Validator
{
    public function is_valid($value): bool
    {
        return \true;
    }
    public function get_messages(): array
    {
        return [];
    }
}
