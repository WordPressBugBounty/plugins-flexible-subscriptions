<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

class Placeholder extends \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\NoValueField {

	public function get_template_name(): string {
		return 'placeholder';
	}

	public function should_override_form_template(): bool {
		return \true;
	}
}
