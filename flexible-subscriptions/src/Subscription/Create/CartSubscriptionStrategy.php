<?php

namespace WPDesk\FlexibleSubscriptions\Subscription\Create;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;

class CartSubscriptionStrategy implements SubscriptionCreationStrategy {

	private SubscriptionCandidate $candidate;

	public function __construct( SubscriptionCandidate $candidate ) {
		$this->candidate = $candidate;
	}

	public function apply_totals( Subscription $subscription ): void {
		$cart = $this->candidate->get_cart();
		$subscription->set_discount_total( $cart->get_cart_discount_total() );
		$subscription->set_discount_tax( $cart->get_cart_discount_tax_total() );
		$subscription->set_cart_tax( $cart->get_cart_contents_tax() + $cart->get_fee_tax() );
		$subscription->set_total( $this->candidate->get_total() );
	}

	public function add_auxiliary_lines( Subscription $subscription, \WC_Order $parent_order ): void {
		$cart = $this->candidate->get_cart();

		\WC()->checkout()->create_order_fee_lines( $subscription, $cart );
		\WC()->checkout()->create_order_tax_lines( $subscription, $cart );
		\WC()->checkout()->create_order_coupon_lines( $subscription, $cart );

		$this->add_shipping_from_cart( $subscription );
	}

	public function add_line_items( Subscription $subscription ): void {
		\WC()->checkout()->create_order_line_items( $subscription, $this->candidate->get_cart() );
	}

	public function requires_manual_renewal( \WC_Order $parent_order, PaymentMethodSeeker $seeker ): bool {
		if ( ! $this->candidate->needs_payment() ) {
			return true;
		}

		$gateway = $seeker->available_gateway( $parent_order->get_payment_method( 'edit' ) );

		if ( ! $gateway instanceof \WC_Payment_Gateway ) {
			return true;
		}

		return ! $gateway->supports( 'subscriptions' );
	}

	private function add_shipping_from_cart( Subscription $subscription ): void {
		if ( $this->candidate->needs_shipping() ) {
			$subscription->set_shipping_total( $this->candidate->get_cart()->get_shipping_total() );
			$subscription->set_shipping_tax( $this->candidate->get_cart()->get_shipping_tax() );

			foreach ( $this->candidate->get_shipping_packages() as $recurring_cart_package_key => $recurring_cart_package ) {
				$package_index      = $recurring_cart_package['package_index'] ?? 0;
				$package            = \WC()->shipping()->calculate_shipping_for_package( $recurring_cart_package );
				$shipping_method_id = \WC()->checkout()->shipping_methods[ $package_index ] ?? '';

				if ( isset( \WC()->checkout()->shipping_methods[ $recurring_cart_package_key ] ) ) {
					$shipping_method_id = \WC()->checkout()->shipping_methods[ $recurring_cart_package_key ];
					$package_key        = $recurring_cart_package_key;
				} else {
					$package_key = $package_index;
				}

				if ( isset( $package['rates'][ $shipping_method_id ] ) ) {
					$shipping_rate = $package['rates'][ $shipping_method_id ];
					$item          = new \WC_Order_Item_Shipping();
					$item->set_props(
						[
							'method_title' => $shipping_rate->label,
							'total'        => wc_format_decimal( $shipping_rate->cost ),
							'taxes'        => [ 'total' => $shipping_rate->taxes ],
							'order_id'     => $subscription->get_id(),
						]
					);

					$item->set_method_id( $shipping_rate->method_id );
					$item->set_instance_id( $shipping_rate->instance_id );

					foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
						$item->add_meta_data( $key, $value, true );
					}

					$subscription->add_item( $item );

					do_action(
						'woocommerce_checkout_create_order_shipping_item',
						$item,
						$package_key,
						$package,
						$subscription
					);
				}
			}
		}
	}
}
