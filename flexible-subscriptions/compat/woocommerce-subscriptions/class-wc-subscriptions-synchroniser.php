<?php

// No subscriptions sync support.
class WC_Subscriptions_Synchroniser {

	public static function is_payment_upfront( $product, $from_date = '' ) {
		return false;
	}

	public static function is_today( $timestamp ) {
		$timestamp += (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		return gmdate( 'Y-m-d', current_time( 'timestamp' ) ) == gmdate( 'Y-m-d', $timestamp );
	}

	public static function is_product_synced( $product ) {
		return false;
	}

	public static function calculate_first_payment_date( $product, $type = 'mysql', $from_date = '' ) {
		return 0;
	}

	public static function maybe_set_free_trial( $total = '' ) {
		return $total;
	}

	public static function maybe_unset_free_trial( $total = '' ) {
		return $total;
	}

}
