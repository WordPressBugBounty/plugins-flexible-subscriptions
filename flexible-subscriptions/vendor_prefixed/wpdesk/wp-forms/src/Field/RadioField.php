<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

class RadioField extends BasicField
{
    public function get_type(): string
    {
        return 'radio';
    }
    public function get_template_name(): string
    {
        return 'input-radio';
    }
}
