<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

/**
 * This renewal method follows WooCommerce subscritpions, as we want to support all WCS-compatible
 * gateways out of the box.
 */
final class AutomaticRequest implements RequestPaymentStrategy {

	public function supports( Subscription $subscription, WC_Order $payment_request ): bool {
		return ! $subscription->is_manual() &&
			! $subscription->is_finalized() &&
			$payment_request->get_payment_method() &&
			$payment_request->get_total() > 0;
	}

	public function request_payment( WC_Order $payment_request ): void {
		// We have to make sure payment gateways are loaded.
		WC()->payment_gateways();

		do_action( 'fsub/subscription/payment_request/pay', $payment_request );
	}
}
