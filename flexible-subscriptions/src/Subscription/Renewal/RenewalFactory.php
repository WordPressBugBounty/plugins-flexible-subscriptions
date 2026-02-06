<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\OrderDataTransfer\OrderDataTransfer;

/**
 * Factory for creating new Renewal domain objects.
 */
final class RenewalFactory {

	private OrderDataTransfer $order_transfer;

	public function __construct( OrderDataTransfer $order_transfer ) {
		$this->order_transfer = $order_transfer;
	}

	/**
	 * Create a new renewal order for the given subscription.
	 */
	public function create_for( Subscription $subscription ): Renewal {
		$order = wc_create_order(
			[
				'customer_id'   => $subscription->get_user_id(),
				'customer_note' => $subscription->get_customer_note(),
				'created_via'   => 'subscription',
				'parent'        => $subscription->get_id(),
			]
		);

		assert( $order instanceof \WC_Order );

		$this->order_transfer->copy( $subscription, $order );

		if ( ! $subscription->is_manual() ) {
			$order->set_payment_method( $subscription->get_payment_method() );
			$order->set_payment_method_title( $subscription->get_payment_method_title() );
		}

		$order->add_meta_data( Renewal::META_ORDER_TYPE, Renewal::ORDER_TYPE_VALUE, true );
		$order->add_meta_data( Renewal::META_SUBSCRIPTION_ID, (string) $subscription->get_id(), false );
		$order->add_meta_data( Renewal::META_BILLING_CYCLE, (string) $subscription->get_billing_cycle(), true );

		$renewal = Renewal::from_order( $order );
		assert( $renewal instanceof Renewal );

		return $renewal;
	}
}
