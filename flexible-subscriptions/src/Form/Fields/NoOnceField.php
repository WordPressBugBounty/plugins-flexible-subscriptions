<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\NoOnceField as BaseNoOnce;

class NoOnceField extends BaseNoOnce {

	public function should_override_form_template(): bool {
		return true;
	}
}
