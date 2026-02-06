<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

class WPEditorField extends BasicField
{
    public function get_template_name(): string
    {
        return 'wp-editor';
    }
}
