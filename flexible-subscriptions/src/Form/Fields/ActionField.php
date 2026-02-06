<?php

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\BasicField;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Sanitizer\TextFieldSanitizer;

class ActionField extends BasicField {

	public function should_override_form_template(): bool {
		return true;
	}

	public function get_type(): string {
		return 'hidden';
	}

	public function get_sanitizer(): Sanitizer {
		return new TextFieldSanitizer();
	}

	public function get_template_name(): string {
		return 'action';
	}
}
