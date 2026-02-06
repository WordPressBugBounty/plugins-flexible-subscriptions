<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

class ToggleField extends CheckboxField
{
    public function __construct()
    {
        $this->add_class('wpd-toggle-field');
        $this->set_sublabel('');
        // Require <label> tag for proper styles application
    }
    public function get_template_name(): string
    {
        return 'input-toggle';
    }
}
