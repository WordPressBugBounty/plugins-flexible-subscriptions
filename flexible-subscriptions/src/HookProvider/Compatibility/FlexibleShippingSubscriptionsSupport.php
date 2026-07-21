<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Compatibility;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Allows Flexible Shipping integrations built for WCS subscriptions to accept FSB subscriptions as well.
 */
final class FlexibleShippingSubscriptionsSupport implements HookProvider {

	public function hooks(): void {
		add_filter(
			'flexible-shipping/shipment/supported-order-type/paczkomaty',
			[ $this, 'support_fsb_subscription_type' ],
			10,
			2
		);
	}

	public function support_fsb_subscription_type( bool $supported, string $order_type ): bool {
		if ( Subscription::OBJECT_TYPE === $order_type ) {
			return true;
		}

		return $supported;
	}
}
