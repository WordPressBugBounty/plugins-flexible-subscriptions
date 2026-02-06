<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class SubscriptionActions implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_order_actions', $this );
	}

	/**
	 * @param array<string, string> $actions
	 *
	 * @return array<string, string>
	 */
	public function __invoke( $actions ) {
		global $theorder;

		if ( ! $theorder instanceof Subscription ) {
			return $actions;
		}

		unset( $actions['send_order_details'], $actions['send_order_details_admin'] );

		return $actions;
	}
}
