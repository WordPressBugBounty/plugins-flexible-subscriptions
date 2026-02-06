<?php

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WPDesk\FlexibleSubscriptions\Integration\FlexibleSubscriptions;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class Init implements HookProvider {

	private FlexibleSubscriptions $fsub;

	public function __construct( FlexibleSubscriptions $fsub ) {
		$this->fsub = $fsub;
	}

	public function hooks(): void {
		add_action( 'init', $this );
	}

	public function __invoke(): void {
		do_action( 'fsub/initialized', $this->fsub );
	}
}
