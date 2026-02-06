<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;
class SelectField extends BasicField
{
    public function get_type(): string
    {
        return 'select';
    }
    public function get_template_name(): string
    {
        return 'select';
    }
    /** @param string[] $options */
    public function set_options(array $options): self
    {
        $this->meta['possible_values'] = $options;
        return $this;
    }
    public function set_multiple(): self
    {
        $this->attributes['multiple'] = 'multiple';
        return $this;
    }
}
