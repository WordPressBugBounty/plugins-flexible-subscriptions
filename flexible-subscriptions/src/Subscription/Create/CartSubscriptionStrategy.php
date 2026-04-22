<?php

namespace WPDesk\FlexibleSubscriptions\Subscription\Create;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Utils\CartContext;

class CartSubscriptionStrategy implements SubscriptionCreationStrategy {

	private SubscriptionCandidate $candidate;

	private CartContext $cart_context;

	public function __construct( SubscriptionCandidate $candidate, CartContext $cart_context ) {
		$this->candidate    = $candidate;
		$this->cart_context = $cart_context;
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

			foreach ( $this->candidate->get_shipping_packages() as $package_key => $recurring_cart_package ) {
				$package            = $this->cart_context->temporarily_promote_to_global(
					$this->candidate->get_cart(),
					static function () use ( $recurring_cart_package, $package_key ) {
						return \WC()->shipping()->calculate_shipping_for_package( $recurring_cart_package, $package_key );
					}
				);
				$package_rates      = $this->extract_rates( $package );
				$shipping_method_id = $this->candidate->get_shipping_method( $package_key ) ?? '';

				if ( empty( $shipping_method_id ) ) {
					$shipping_method_id = \WC()->checkout()->shipping_methods[ $package_key ] ?? '';
				}

				if ( empty( $shipping_method_id ) && ! empty( $package_rates ) ) {
					$first_rate         = reset( $package_rates );
					$shipping_method_id = $first_rate ? $first_rate->id : '';
				}

				if ( isset( $package_rates[ $shipping_method_id ] ) ) {
					$shipping_rate = $package_rates[ $shipping_method_id ];
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

	/**
	 * @param array<string, mixed>|false $package
	 * @return array<string, \WC_Shipping_Rate>
	 */
	private function extract_rates( $package ): array {
		$rates = is_array( $package ) ? ( $package['rates'] ?? [] ) : [];

		return is_array( $rates ) ? $rates : [];
	}
}
