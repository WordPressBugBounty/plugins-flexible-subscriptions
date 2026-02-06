<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Payment;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class PaymentRequestFinder {

	/** @param int|\WP_Post $id */
	public function find( $id ): ?\WC_Order {
		$payment_request = wc_get_order( $id );
		if ( $payment_request instanceof \WC_Order ) {
			return $payment_request;
		}

		return null;
	}

	/** @return \WC_Order[] */
	public function find_for_subscription( Subscription $subscription ): array {
		$requests = wc_get_orders(
			[
				'parent' => $subscription->get_id(),
				'status' => 'any',
			]
		);

		return $requests;
	}
}
