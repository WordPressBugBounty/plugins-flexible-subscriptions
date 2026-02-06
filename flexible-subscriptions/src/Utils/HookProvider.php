<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;

interface HookProvider extends Hookable {

	public function hooks(): void;
}
