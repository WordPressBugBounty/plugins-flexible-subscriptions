<?php

use WPDesk\FlexibleSubscriptions\Compatibility\CastingSubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;

if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
/**
 * Checks an order to see if it contains a subscription.
 *
 * @param mixed $order A WC_Order object or the ID of the order which the subscription was purchased in.
 * @param array|string $order_type Can include 'parent', 'renewal', 'resubscribe' and/or 'switch'. Defaults to 'parent', 'resubscribe' and 'switch' orders.
 * @return bool True if the order contains a subscription that belongs to any of the given order types, otherwise false.
 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v2.0
 */
function wcs_order_contains_subscription( $order, $order_type = array( 'parent', 'resubscribe', 'switch' ) ) {
	$finder = new SubscriptionFinder();
	$order = wc_get_order( $order );

	if ( ! $order instanceof \WC_Order ) {
		return false;
	}

	$order_type = (array) $order_type;

	if (! empty($finder->find_all_by_order( $order )) ) {
		return true;
	}

	if ($finder->find_by_payment_request($order->get_id()) instanceof Subscription) {
		return true;
	}

	return false;
}
}

if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
/**
 * Get the subscription related to an order, if any.
 *
 * @param WC_Order|int $order An instance of a WC_Order object or the ID of an order
 * @param array $args A set of name value pairs to filter the returned value.
 *    'subscriptions_per_page' The number of subscriptions to return. Default set to -1 to return all.
 *    'offset' An optional number of subscription to displace or pass over. Default 0.
 *    'orderby' The field which the subscriptions should be ordered by. Can be 'start_date', 'trial_end_date', 'end_date', 'status' or 'order_id'. Defaults to 'start_date'.
 *    'order' The order of the values returned. Can be 'ASC' or 'DESC'. Defaults to 'DESC'
 *    'customer_id' The user ID of a customer on the site.
 *    'product_id' The post ID of a WC_Product_Subscription, WC_Product_Variable_Subscription or WC_Product_Subscription_Variation object
 *    'order_id' The post ID of a shop_order post/WC_Order object which was used to create the subscription
 *    'subscription_status' Any valid subscription status. Can be 'any', 'active', 'cancelled', 'on-hold', 'expired', 'pending' or 'trash'. Defaults to 'any'.
 *    'order_type' Get subscriptions for the any order type in this array. Can include 'any', 'parent', 'renewal' or 'switch', defaults to parent.
 * @return WC_Subscription[] Subscription details in post_id => WC_Subscription form.
 * @since  1.0.0 - Migrated from WooCommerce Subscriptions v2.0
 */
function wcs_get_subscriptions_for_order( $order, $args = array() ) {
	$finder = new CastingSubscriptionFinder(new SubscriptionFinder(), \WC_Subscription::class);
	$order = wc_get_order( $order );

	if ( ! $order instanceof \WC_Order ) {
		return [];
	}

	$subscriptions = $finder->find_all_by_order( $order );
	if (! empty($subscriptions) ) {
		return $subscriptions;
	}

	$subscription = $finder->find_by_payment_request($order->get_id());
	if ($subscription instanceof Subscription) {
		return [$subscription];
	}

	return [];
}
}
