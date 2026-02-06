<?php

use WPDesk\FlexibleSubscriptions\Compatibility\CastingSubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;

if ( ! function_exists( 'wcs_user_has_subscription' ) ) {
	/**
	 * Check if a user has a subscription, optionally to a specific product and/or with a certain status.
	 *
	 * @param int $user_id (optional) The ID of a user in the store. If left empty, the current user's ID will be used.
	 * @param int $product_id (optional) The ID of a product in the store. If left empty, the function will see if the user has any subscription.
	 * @param mixed $status (optional) A valid subscription status string or array. If left empty, the function will see if the user has a subscription of any status.
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v2.0
	 *
	 * @return bool
	 */
	function wcs_user_has_subscription( $user_id = 0, $product_id = '', $status = 'any' ) {
		$finder = new SubscriptionFinder();
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id === 0 ) {
			return false;
		}

		$limit = -1;
		if ( ! $product_id && $status === 'any' ) {
			$limit = 1;
		}

		$subscriptions = $finder->find_by_customer( $user_id, 0, $limit );

		if ( $limit === 1 && count( $subscriptions ) > 0 ) {
			return true;
		}

		$subscriptions_with_status = array_filter(
			$subscriptions,
		   	function ( $subscription ) use ( $status ) {
				return $subscription->has_status( $status );
			}
		);

		if ( empty( $subscriptions_with_status ) ) {
			return false;
		}

		$subscriptions_with_product = array_filter(
			$subscriptions,
		   	function ( $subscription ) use ( $product_id ) {
				foreach ( $subscription->get_items() as $item ) {
					if (
						$item instanceof \WC_Order_Item_Product &&
						$item->get_product_id() === $product_id
					) {
						return true;
					}
				}
		   	}
		);

		if ( empty( $subscriptions_with_product ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
	/**
	 * Get all of a user's subscriptions.
	 *
	 * @param int $user_id The ID of the user. If 0, the current user is used.
	 * @return WC_Subscription[] An array of subscription objects.
	 */
	function wcs_get_users_subscriptions( $user_id = 0 ) {
		if ( 0 === $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return [];
		}

		$finder = new CastingSubscriptionFinder( new SubscriptionFinder(), \WC_Subscription::class );
		$subscriptions = $finder->find_by_customer( $user_id, 0, -1 ); // -1 for no limit

		return is_array( $subscriptions ) ? $subscriptions : [];
	}
}
