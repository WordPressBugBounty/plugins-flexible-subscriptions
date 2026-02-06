<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcs_resubscribe_order_created' ) ) {
	/**
	 * Dummy function for resubscribe compatibility.
	 * Flexible Subscriptions does not support this feature.
	 *
	 * @param \WC_Order       $resubscribe_order
	 * @param \WC_Subscription $subscription
	 */
	function wcs_resubscribe_order_created( $resubscribe_order, $subscription ) {
		// This is intentionally left empty.
		// Gateways may check for this action, but FSB does not implement resubscribing.
	}
}
