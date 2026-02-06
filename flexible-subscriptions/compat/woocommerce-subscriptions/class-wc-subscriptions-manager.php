<?php

if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
	return;
}

class WC_Subscriptions_Manager {

	/**
	 * Activates all subscriptions in a given order.
	 *
	 * This is called by gateways after a successful payment for an order
	 * containing a new subscription.
	 *
	 * @param WC_Order|int $order The order or order ID.
	 */
	public static function activate_subscriptions_for_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		// Use the WCS compatibility function to get all subscriptions.
		$subscriptions = wcs_get_subscriptions_for_order( $order );

		foreach ( $subscriptions as $subscription ) {
			/** @var WC_Subscription $subscription */
			if ( $subscription->needs_payment() ) {
				// The `payment_complete` method will internally handle status transition to 'active'.
				$subscription->payment_complete();
			}
		}
	}
}
