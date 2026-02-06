<?php

if ( class_exists( 'WCS_Retry_Store' ) ) {
	return;
}

class WCS_Retry_Store {

	/**
	 * Get the details of the last retry (if any) recorded for a given order.
	 *
	 * @param int $order_id The ID of the order (usually a renewal order).
	 * @return null
	 */
	public function get_last_retry_for_order( $order_id ) {
		// Not supported
		return null;
	}
}
