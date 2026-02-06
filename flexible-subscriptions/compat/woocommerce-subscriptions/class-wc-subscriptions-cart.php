<?php

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;

if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
	return;
}

class WC_Subscriptions_Cart {

	private static ?SubscriptionCandidatesList $fsb_candidates = null;

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

	public static function set_calculation_type( string $calculation_type ): void {
	}
}
