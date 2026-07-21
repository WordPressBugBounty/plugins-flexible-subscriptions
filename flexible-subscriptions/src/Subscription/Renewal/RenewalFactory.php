<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WPDesk\FlexibleSubscriptions\Subscription\OrderDataCopier;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

/**
 * Factory for creating new Renewal domain objects.
 */
final class RenewalFactory {

	private OrderDataCopier $order_data_copier;

	public function __construct( OrderDataCopier $order_data_copier ) {
		$this->order_data_copier = $order_data_copier;
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

		$this->order_data_copier->copy_renewal_data( $subscription, $order );

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
