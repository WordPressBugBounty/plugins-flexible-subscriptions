<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

class ButtonField extends NoValueField
{
    public function get_template_name(): string
    {
        return 'button';
    }
    public function get_type(): string
    {
        return 'button';
    }
}
