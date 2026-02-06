<?php

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Formatting\Price\ProductFullPrice;

if ( class_exists( 'WC_Subscriptions_Product' ) ) {
	return;
}

class WC_Subscriptions_Product {

	/**
	 * Checks a given product to determine if it is a subscription.
	 *
	 * @param int|\WC_Product $product Either a product object or product's post ID.
	 *
	 * @return bool
	 */
	public static function is_subscription( $product ) {
		if ( $product instanceof SubscriptionProduct ) {
			return true;
		}

		if ( is_numeric( $product ) ) {
			return wc_get_product( $product ) instanceof SubscriptionProduct;
		}

		return false;
	}

	/**
	 * Returns the trial length.
	 *
	 * @param mixed $product A WC_Product object or product ID
	 * @return int Length of the trial, or 0 if none.
	 */
	public static function get_trial_length( $product ) {
		$product_instance = self::get_fsb_product_wrapper( $product );
		if ( ! $product_instance ) {
			return 0;
		}

		return $product_instance->get_trial_length();
	}

	/**
	 * Returns the sign-up fee for a subscription.
	 *
	 * @param mixed $product A WC_Product object or product ID
	 * @return string|float The value of the sign-up fee, or 0.
	 */
	public static function get_sign_up_fee( $product ) {
		$product_instance = self::get_fsb_product_wrapper( $product );
		if ( ! $product_instance ) {
			return 0;
		}

		return $product_instance->get_signup_fee() ?: 0;
	}

	private static function get_fsb_product_wrapper( $product ): ?SubscriptionProductWrapper {
		if ( $product instanceof SubscriptionProduct ) {
			return new SubscriptionProductWrapper( $product );
		}

		if ( is_numeric( $product ) ) {
			$_product = wc_get_product( $product );
			if ( $_product instanceof SubscriptionProduct ) {
				return new SubscriptionProductWrapper( $_product );
			}
		}

		return null;
	}

	/**
	 * Returns a string representing the details of the subscription.
	 *
	 * For example "$20 per Month for 3 Months with a $10 sign-up fee".
	 *
	 * @param WC_Product|int $product A WC_Product object or ID of a WC_Product.
	 * @param array $inclusions An associative array of flags to indicate how to calculate the price and what to include, values:
	 *    'tax_calculation'     => false to ignore tax, 'include_tax' or 'exclude_tax' To indicate that tax should be added or excluded respectively
	 *    'subscription_length' => true to include subscription's length (default) or false to exclude it
	 *    'sign_up_fee'         => true to include subscription's sign up fee (default) or false to exclude it
	 *    'price'               => string a price to short-circuit the price calculations and use in a string for the product
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v1.0
	 */
	public static function get_price_string( $product, $include = [] ) {
		return (string) new ProductFullPrice( self::get_fsb_product_wrapper( $product ), $include );
	}

	/**
	 * Returns the subscription interval for a product, if it's a subscription.
	 *
	 * @param mixed $product A WC_Product object or product ID
	 * @return int An integer representing the subscription interval, or 1 if the product is not a subscription or there is no interval
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v1.0
	 */
	public static function get_interval( $product ) {
		return self::get_fsb_product_wrapper( $product )->get_payment_interval() ?: 1;
	}

	/**
	 * Returns the length of a subscription product, if it is a subscription.
	 *
	 * @param mixed $product A WC_Product object or product ID
	 * @return int An integer representing the length of the subscription, or 0 if the product is not a subscription or the subscription continues for perpetuity
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v1.0
	 */
	public static function get_length( $product ) {
		return self::get_fsb_product_wrapper( $product )->get_length() ?: 0;
	}

	/**
	 * Returns the subscription period for a product, if it's a subscription.
	 *
	 * @param mixed $product A WC_Product object or product ID
	 * @return string A string representation of the period, either Day, Week, Month or Year, or an empty string if product is not a subscription.
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v1.0
	 */
	public static function get_period( $product ) {
		$period = self::get_fsb_product_wrapper( $product )->get_payment_period();

		switch ( strtoupper( $period ) ) {
			case 'D':
				return 'day';
			case 'W':
				return 'week';
			case 'Y':
				return 'year';
			case 'M':
			default:
				return 'month';
		}
	}
}
