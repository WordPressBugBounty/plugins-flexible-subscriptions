<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

final class InFlightRenewalOrderFinder {

	/**
	 * Finds an existing in-flight renewal for the given subscription and billing cycle.
	 * Cancelled orders are intentionally ignored.
	 */
	public function find( Subscription $subscription, int $billing_cycle ): ?Renewal {
		$subscription_id = $subscription->get_id();

		$recent_id = $subscription->get_recent_payment_request_id();
		if ( $recent_id ) {
			$recent  = wc_get_order( $recent_id );
			$renewal = $this->to_in_flight_renewal( $recent, $subscription_id, $billing_cycle );
			if ( $renewal instanceof Renewal ) {
				return $renewal;
			}
		}

		$orders = wc_get_orders(
			[
				'parent'     => $subscription_id,
				'limit'      => 20,
				'orderby'    => 'date',
				'order'      => 'DESC',
				'status'     => 'any',
				'meta_query' => [
					[
						'key'   => Renewal::META_ORDER_TYPE,
						'value' => Renewal::ORDER_TYPE_VALUE,
					],
					[
						'key'     => Renewal::META_BILLING_CYCLE,
						'value'   => (string) $billing_cycle,
						'compare' => '=',
					],
				],
			]
		);

		foreach ( $orders as $order ) {
			$renewal = $this->to_in_flight_renewal( $order, $subscription_id, $billing_cycle );
			if ( $renewal instanceof Renewal ) {
				return $renewal;
			}
		}

		return null;
	}

	/**
	 * Attempt to convert a WC_Order to an in-flight Renewal.
	 * Returns null if the order is not a valid in-flight renewal for the given subscription.
	 *
	 * @param mixed $order
	 */
	private function to_in_flight_renewal( $order, int $subscription_id, int $billing_cycle ): ?Renewal {
		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		if ( (int) $order->get_parent_id() !== $subscription_id ) {
			return null;
		}

		$renewal = Renewal::from_order( $order );
		if ( ! $renewal instanceof Renewal ) {
			return null;
		}

		if ( ! $renewal->is_in_flight() ) {
			return null;
		}

		$renewal_cycle = $renewal->get_billing_cycle();
		if ( $renewal_cycle !== 1 && $renewal_cycle !== $billing_cycle ) {
			return null;
		}

		return $renewal;
	}
}
