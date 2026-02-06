<?php

use WPDesk\FlexibleSubscriptions\Subscription\Payment\PaymentRequestFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

if ( class_exists( 'WC_Subscription' ) ) {
	return;
}

class WC_Subscription extends Subscription {

	public function get_payment_method_to_display(): string {
		return apply_filters( 'woocommerce_my_subscriptions_payment_method', parent::get_payment_method_to_display(), $this );
	}

	/**
	 * Get the timestamp for a specific piece of the subscriptions schedule.
	 *
	 * @param string $date_type 'date_created', 'trial_end', 'next_payment', 'last_order_date_created', 'end', 'start'.
	 * @param string $timezone  Context ('gmt' or 'site'). WCS often uses 'gmt'. Timestamps are inherently UTC ('gmt').
	 * @return int|string Unix timestamp or empty string if date not set. WCS sometimes returns 0. Check specific gateway needs.
	 */
	public function get_time( $date_type, $timezone = 'gmt' ) {
		$date = null;

		switch ( $date_type ) {
			case 'start':
			case 'date_created':
				$date = $this->get_start_date();
				break;
			case 'trial_end':
				$date = $this->get_trial_end_date();
				break;
			case 'next_payment':
				$date = $this->get_current_period_end();
				break;
			case 'last_order_date_created':
				$last_order_id = $this->get_recent_payment_request_id();
				if ( $last_order_id ) {
					$last_order = wc_get_order( $last_order_id );
					if ( $last_order ) {
						$date = $last_order->get_date_created();
					}
				}
				break;
			case 'end':
				$date = $this->get_end_date();
				break;
			case 'cancelled':
				$date = $this->get_cancelled_date();
				break;
			default:
				$date = null;
		}

		if ( $date instanceof \DateTimeInterface ) {
			return $date->format( 'U' );
		}

		return 0;
	}

	public function get_billing_period() {
		switch ( $this->get_billing_frequency()->unit() ) {
			case 'D': return 'day';
			case 'W': return 'week';
			case 'M': return 'month';
			case 'Y': return 'year';
			default:  return 'month';
		}
	}

	public function get_billing_interval() {
		return $this->get_billing_frequency()->length();
	}

	/**
	 * Process a failed payment.
	 *
	 * @param string $new_status The status to set if payment fails. Defaults to 'on-hold'.
	 */
	public function payment_failed( $new_status = 'on-hold' ): void {
		$last_order = $this->get_recent_payment_request_id() ? wc_get_order( $this->get_recent_payment_request_id() ) : null;

		if ( $last_order instanceof \WC_Order && ! $last_order->has_status( 'failed' ) ) {
			$last_order->update_status( 'failed', __( 'Subscription renewal payment failed.', 'flexible-subscriptions' ) );
		}

		$this->add_order_note( __( 'Payment failed.', 'flexible-subscriptions' ) );

		$max_failed_exceeded = apply_filters( 'woocommerce_subscription_max_failed_payments_exceeded', false, $this );

		if ( 'cancelled' === $new_status || $max_failed_exceeded ) {
			if ( $this->can_update_status( 'cancelled' ) ) {
				$this->update_status( 'cancelled', __( 'Subscription Cancelled: maximum number of failed payments reached.', 'flexible-subscriptions' ) );
			}
		} elseif ( $this->can_update_status( $new_status ) ) {
			$this->update_status( $new_status );
		}
	}

	/**
	 * When payment is completed for a related order.
	 *
	 * @param WC_Order $order The order for which payment was completed.
	 */
	public function payment_complete_for_order( $last_order ) {
		parent::payment_complete( $last_order->get_transaction_id() );
	}

	/**
	 * Get the related orders for a subscription.
	 *
	 * @param string $return_fields 'ids' or 'all'.
	 * @param array|string $order_types 'parent', 'renewal', 'switch', 'resubscribe', 'any'.
	 * @return array Array of WC_Order objects or IDs.
	 */
	public function get_related_orders( $return_fields = 'ids', $order_types = [ 'parent', 'renewal' ] ) {
		$order_types = empty( $order_types ) ? [ 'parent', 'renewal' ] : (array) $order_types;

		$related_orders_objects = [];

		if ( in_array( 'parent', $order_types, true ) || in_array( 'any', $order_types, true ) ) {
			$parent_order = $this->get_parent();
			if ( $parent_order ) {
				$related_orders_objects[ $parent_order->get_id() ] = $parent_order;
			}
		}

		if ( in_array( 'renewal', $order_types, true ) || in_array( 'any', $order_types, true ) ) {
			$finder = new PaymentRequestFinder();
			$renewal_orders = $finder->find_for_subscription( $this );
			foreach ( $renewal_orders as $renewal_order ) {
				$related_orders_objects[ $renewal_order->get_id() ] = $renewal_order;
			}
		}

		if ( 'ids' === $return_fields ) {
			$related_orders = array_keys( $related_orders_objects );
			$related_orders = array_combine( $related_orders, $related_orders );
		} else {
			$related_orders = $related_orders_objects;
		}

		if ( $related_orders ) {
			krsort( $related_orders );
		}

		return apply_filters( 'woocommerce_subscription_related_orders', $related_orders, $this->get_id(), $return_fields, $order_types );
	}
}
