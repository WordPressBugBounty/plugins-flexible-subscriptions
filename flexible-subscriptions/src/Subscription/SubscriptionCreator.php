<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Cart\ApiSubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidateInterface;
use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Subscription\Create\ApiSubscriptionStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Create\CartSubscriptionStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Create\SubscriptionCreationStrategy;
use WPDesk\FlexibleSubscriptions\Utils\CartContext;

class SubscriptionCreator {

	private PaymentMethodSeeker $gateway_seeker;

	private CartContext $cart_context;

	public function __construct( PaymentMethodSeeker $gateway_seeker, CartContext $cart_context ) {
		$this->gateway_seeker = $gateway_seeker;
		$this->cart_context   = $cart_context;
	}

	public function build_subscription( \WC_Order $order, SubscriptionCandidateInterface $candidate ): Subscription {
		$subscription       = new Subscription();
		$candidate_strategy = $this->get_strategy( $candidate );

		$subscription->set_parent_id( $order->get_id() );
		$subscription->set_customer_id( $order->get_customer_id( 'edit' ) );
		$subscription->set_currency( $order->get_currency( 'edit' ) );
		$subscription->set_prices_include_tax( $order->get_prices_include_tax( 'edit' ) );
		$subscription->set_customer_note( $order->get_customer_note( 'edit' ) );

		$created_via = $candidate->get_created_via();
		$subscription->set_created_via( $created_via === 'checkout' ? $order->get_created_via( 'edit' ) : $created_via );

		$subscription->set_date_created( $candidate->get_start_date()->format( 'Y-m-d H:i:s' ) );

		$this->set_addresses( $subscription, $order );

		$subscription->set_payment_method( $order->get_payment_method( 'edit' ) );
		$subscription->set_payment_method_title( $order->get_payment_method_title( 'edit' ) );

		$subscription->set_start_date( $candidate->get_start_date() );
		$subscription->set_billing_frequency( $candidate->get_billing_frequency() );
		$subscription->set_trial_end_date( $candidate->get_trial_end_date() );
		$subscription->set_expiration( $candidate->get_expiration() );

		$subscription->recalculate_periods();

		$subscription->set_manual(
			$candidate_strategy->requires_manual_renewal(
				$order,
				$this->gateway_seeker
			)
		);

		$candidate_strategy->add_line_items( $subscription );
		$candidate_strategy->add_auxiliary_lines( $subscription, $order );

		$candidate_strategy->apply_totals( $subscription );

		$subscription->save();

		return $subscription;
	}

	/**
	 * Copy the billing and shipping addresses from order to subscription.
	 */
	private function set_addresses( Subscription $subscription, \WC_Order $order ): void {
		$subscription->set_shipping_first_name( $order->get_shipping_first_name() );
		$subscription->set_shipping_last_name( $order->get_shipping_last_name() );
		$subscription->set_shipping_company( $order->get_shipping_company() );
		$subscription->set_shipping_address_1( $order->get_shipping_address_1() );
		$subscription->set_shipping_address_2( $order->get_shipping_address_2() );
		$subscription->set_shipping_city( $order->get_shipping_city() );
		$subscription->set_shipping_state( $order->get_shipping_state() );
		$subscription->set_shipping_postcode( $order->get_shipping_postcode() );
		$subscription->set_shipping_country( $order->get_shipping_country() );
		$subscription->set_billing_first_name( $order->get_billing_first_name() );
		$subscription->set_billing_last_name( $order->get_billing_last_name() );
		$subscription->set_billing_company( $order->get_billing_company() );
		$subscription->set_billing_address_1( $order->get_billing_address_1() );
		$subscription->set_billing_address_2( $order->get_billing_address_2() );
		$subscription->set_billing_city( $order->get_billing_city() );
		$subscription->set_billing_state( $order->get_billing_state() );
		$subscription->set_billing_postcode( $order->get_billing_postcode() );
		$subscription->set_billing_country( $order->get_billing_country() );
		$subscription->set_billing_email( $order->get_billing_email() );
		$subscription->set_billing_phone( $order->get_billing_phone() );
	}

	private function get_strategy( SubscriptionCandidateInterface $candidate ): SubscriptionCreationStrategy {
		if ( $candidate instanceof SubscriptionCandidate ) {
			return new CartSubscriptionStrategy( $candidate, $this->cart_context );
		}

		if ( $candidate instanceof ApiSubscriptionCandidate ) {
			return new ApiSubscriptionStrategy( $candidate );
		}

		throw new \Exception( 'Unsupported subscription candidate: ' . get_class( $candidate ) );
	}
}
