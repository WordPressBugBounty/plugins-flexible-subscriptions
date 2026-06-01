<?php

use WPDesk\FlexibleSubscriptions\Compatibility\CastingSubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;

if ( ! function_exists( 'wcs_is_subscription' ) ) {
	/**
	 * Check if a given object is a WC_Subscription (compat layer), or if a given ID
	 * belongs to an FSB subscription post type.
	 *
	 * @param mixed $subscription A WC_Subscription object or an ID.
	 * @return boolean true if it's an FSB subscription.
	 */
	function wcs_is_subscription( $subscription ) {
		if ( is_a( $subscription, 'WC_Subscription' ) ) {
			return true;
		}

		if ( $subscription instanceof Subscription ) {
			return false;
		}

		if ( ! is_numeric( $subscription ) || $subscription <= 0 ) {
			return false;
		}

		$finder = new SubscriptionFinder();
		$fsb_subscription = $finder->find( (int) $subscription );

		return $fsb_subscription instanceof Subscription;
	}
}

if ( ! function_exists( 'wcs_get_subscription' ) ) {
	/**
	 * Get a subscription object.
	 *
	 * @param int|WC_Order $subscription The subscription ID or a WC_Order object.
	 * @return WC_Subscription|null A subscription object or null if not found.
	 */
	function wcs_get_subscription( $subscription ) {
		$subscription_id = $subscription instanceof WC_Order ? $subscription->get_id() : $subscription;

		if ( ! is_numeric( $subscription_id ) || $subscription_id <= 0 ) {
			return null;
		}

		// referesh, as WC_Subscription stub class
		$subscription_object = new WC_Subscription( (int) $subscription_id );

		return $subscription_object->get_id() ? $subscription_object : null;
	}
}

if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
	/**
	 * Get all subscriptions.
	 *
	 * @return WC_Subscription[] Array of WC_Subscription objects.
	 */
	function wcs_get_subscriptions( $args ) {
		$finder = new CastingSubscriptionFinder(new SubscriptionFinder(), WC_Subscription::class);
		return $finder->find_all_by( $args );
	}
}

if ( ! function_exists( 'wcs_get_canonical_product_id' ) ) {
	/**
	 * Get the variation ID for variation items or the product ID for non-variation items.
	 *
	 * When acting on cart items or order items, Subscriptions often needs to use an item's canonical product ID. For
	 * items representing a variation, that means the 'variation_id' value, if the item is not a variation, that means
	 * the 'product_id value. This function helps save keystrokes on the idiom to check if an item is to a variation or not.
	 *
	 * @param array or object $item Either a cart item, order/subscription line item, or a product.
	 */
	function wcs_get_canonical_product_id( $item_or_product ) {
		if ( $item_or_product instanceof \WC_Product ) {
			return $item_or_product->get_id();
		}

		if ( $item_or_product instanceof \WC_Order_Item ) {
			return ( $item_or_product->get_variation_id() ) ? $item_or_product->get_variation_id() : $item_or_product->get_product_id();
		}

		if ( is_array( $item_or_product ) ) {
			return ( $item_or_product['variation_id'] ) ? $item_or_product['variation_id'] : $item_or_product['product_id'];
		}

		return 0;
	}
}
