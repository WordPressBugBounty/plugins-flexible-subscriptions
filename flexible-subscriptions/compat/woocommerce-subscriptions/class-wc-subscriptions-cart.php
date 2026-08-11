<?php

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;

if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
	return;
}

class WC_Subscriptions_Cart {

	private static ?SubscriptionCandidatesList $fsb_candidates = null;
	private static string $calculation_type = 'none';

	public static function initialize( SubscriptionCandidatesList $candidates ): void {
		self::$fsb_candidates = $candidates;
	}

	public static function cart_contains_subscription() {
		if ( null === self::$fsb_candidates ) {
			return false;
		}

		return self::$fsb_candidates->count() > 0;
	}

	public static function cart_contains_free_trial() {
		if ( null === self::$fsb_candidates ) {
			return false;
		}

		if ( ! self::cart_contains_subscription() ) {
			return false;
		}

		foreach ( self::$fsb_candidates as $candidate ) {
			if ( $candidate->has_trial() ) {
				return true;
			}
		}

		return false;
	}

	public static function get_calculation_type(): string {
		return self::$calculation_type;
	}

	public static function set_subscription_prices_for_calculation( $price, $product ) {
		if ( WC_Subscriptions_Product::is_subscription( $product ) ) {
			if ( 'none' === self::$calculation_type ) {
				$sign_up_fee = (float) WC_Subscriptions_Product::get_sign_up_fee( $product );
				$price       = WC_Subscriptions_Product::get_trial_length( $product ) > 0
					? $sign_up_fee
					: (float) $price + $sign_up_fee;
			}
		} elseif ( 'recurring_total' === self::$calculation_type ) {
			$price = 0;
		}

		return $price;
	}

	public static function set_calculation_type( string $calculation_type ): void {
		self::$calculation_type = $calculation_type;
	}
}
