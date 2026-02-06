<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

class Tab extends Section {

	public function get_template_name(): string {
		return 'tab';
	}
}
