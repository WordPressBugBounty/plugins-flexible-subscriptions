<?php

use WPDesk\FlexibleSubscriptions\Compatibility\CastingSubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;

if ( ! function_exists('wcs_order_contains_renewal') ) {
	/**
	 * Check if a given order is a subscription renewal order based on FSB structure.
	 * In FSB, a renewal order has the subscription ID as its parent_id.
	 *
	 * @param WC_Order|int $order The WC_Order object or ID of an order.
	 * @return bool
	 */
	function wcs_order_contains_renewal( $order ) {
		$order_obj = wc_get_order( $order );
		if ( ! $order_obj instanceof \WC_Order ) {
			return false;
		}

		$parent_id = $order_obj->get_parent_id( 'edit' );

		if ( $parent_id > 0 ) {
			$finder = new SubscriptionFinder();
			$subscription = $finder->find( $parent_id );
			return $subscription instanceof Subscription;
		}

		return false;
	}
}

if ( ! function_exists('wcs_cart_contains_renewal') ) {
	/**
	 * Checks the cart to see if it contains an item specifically for renewing an existing subscription.
	 * FSB does not support adding renewal items directly to the cart this way.
	 *
	 * @return false Always returns false for FSB.
	 */
	function wcs_cart_contains_renewal(): bool {
		// Flexible Subscriptions handles renewals via scheduled actions, not cart items.
		return false;
	}
}

if ( ! function_exists('wcs_get_subscriptions_for_renewal_order') ) {
	/**
	 * Get the subscription to which a renewal order relates based on FSB structure.
	 *
	 * @param WC_Order|int $order The WC_Order object or ID of a renewal order.
	 * @return WC_Subscription[] Array containing the compat WC_Subscription object, or empty array.
	 */
	function wcs_get_subscriptions_for_renewal_order( $order ): array {
		$order_obj = wc_get_order( $order );
		if ( ! $order_obj instanceof \WC_Order ) {
			return [];
		}

		$parent_id = $order_obj->get_parent_id( 'edit' );

		if ( $parent_id > 0 ) {
			$finder = new CastingSubscriptionFinder( new SubscriptionFinder(), \WC_Subscription::class );
			$subscription = $finder->find( $parent_id );
			if ( $subscription instanceof WC_Subscription ) {
				return [ $subscription->get_id() => $subscription ];
			}
		}

		return [];
	}
}

if ( ! function_exists('wcs_is_manual_renewal_enabled') ) {
	/**
	 * Checks if manual renewals are enabled.
	 *
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v4.0.0
	 * @return bool Whether manual renewal is enabled.
	 */
	function wcs_is_manual_renewal_enabled() {
		return apply_filters( 'fsub/payment/manual_renewal/enabled', true );
	}
}

if ( ! function_exists('wcs_is_manual_renewal_required') ) {
	/**
	 * Checks if manual renewals are required - automatic renewals are disabled.
	 *
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v4.0.0
	 * @return bool Whether manual renewal is required.
	 */
	function wcs_is_manual_renewal_required() {
		return false;
	}
}
