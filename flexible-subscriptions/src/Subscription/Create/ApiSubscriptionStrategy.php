<?php

namespace WPDesk\FlexibleSubscriptions\Subscription\Create;

use WPDesk\FlexibleSubscriptions\Cart\ApiSubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;

class ApiSubscriptionStrategy implements SubscriptionCreationStrategy {

	private ApiSubscriptionCandidate $candidate;

	public function __construct( ApiSubscriptionCandidate $candidate ) {
		$this->candidate = $candidate;
	}

	public function apply_totals( Subscription $subscription ): void {
		$subscription->calculate_totals();
	}

	public function add_auxiliary_lines( Subscription $subscription, \WC_Order $parent_order ): void {
		foreach ( $parent_order->get_shipping_methods() as $shipping_item ) {
			$new_shipping = clone $shipping_item;
			$new_shipping->set_id( 0 );
			$new_shipping->set_order_id( $subscription->get_id() );
			$subscription->add_item( $new_shipping );
		}
	}

	public function add_line_items( Subscription $subscription ): void {
		$new_item = clone $this->candidate->get_order_item();
		$new_item->set_id( 0 );
		$new_item->set_order_id( $subscription->get_id() );
		$subscription->add_item( $new_item );
	}

	public function requires_manual_renewal( \WC_Order $parent_order, PaymentMethodSeeker $seeker ): bool {
		return true;
	}
}
