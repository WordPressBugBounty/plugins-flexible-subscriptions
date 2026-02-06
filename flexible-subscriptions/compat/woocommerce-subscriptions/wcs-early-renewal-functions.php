<?php

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

if ( ! function_exists( 'wcs_get_early_renewal_url' ) ) {
	function wcs_get_early_renewal_url( $subscription ) {
		if ( is_numeric( $subscription ) ) {
			$id = (int) $subscription;
		} elseif ( $subscription instanceof Subscription ) {
			$id = $subscription->get_id();
		}

		if ( ! $id ) {
			return '';
		}

		return add_query_arg(
			[ 'view-fsb-subscription' => $id ],
			get_permalink( wc_get_page_id( 'myaccount' ) )
		);
	}
}
