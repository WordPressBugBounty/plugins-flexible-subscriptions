<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringShippingPackage;
use WPDesk\FlexibleSubscriptions\Utils\CartContext;

final class RecurringShippingResolver {

	private \WC_Shipping $shipping;

	private CartContext $cart_context;

	public function __construct( \WC_Shipping $shipping, CartContext $cart_context ) {
		$this->shipping     = $shipping;
		$this->cart_context = $cart_context;
	}

	/**
	 * @return RecurringShippingPackage[]
	 */
	public function resolve( SubscriptionCandidate $candidate ): array {
		$original_packages = $this->shipping->calculate_shipping( $this->cart_context->get_real_global_cart()->get_shipping_packages() );

		return $this->cart_context->temporarily_promote_to_global(
			$candidate->get_cart(),
			function () use ( $candidate, $original_packages ): array {
				$selections = [];

				foreach ( $candidate->get_shipping_packages() as $package_key => $package ) {
					$shipping            = $this->shipping->calculate_shipping_for_package( $package, $package_key );
					$recurring_rates     = $this->extract_rates( $shipping );
					$match_initial_rates = $this->rates_match_initial_package( $original_packages, $package_key, $recurring_rates );

					$product_names   = array_map( static fn( $values ) => $values['data']->get_name(), $package['contents'] );
					$product_names   = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
					$package_details = implode( ', ', $product_names );

						$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', [] );
						$initial_method          = $chosen_shipping_methods[ $package_key ] ?? $chosen_shipping_methods[ RecurringShippingPackage::index_from_key( $package_key ) ] ?? '';
					if ( $initial_method && isset( $recurring_rates[ $initial_method ] ) ) {
						$selected_method = $initial_method;
					} else {
						$first_rate      = reset( $recurring_rates );
						$selected_method = $first_rate ? (string) $first_rate->id : '';
					}

					$package['package_id']   = $package_key;
					$package['package_name'] = sprintf(
						/* translators: %s: billing frequency (e.g. "month" or "every 2 weeks"). */
						__( 'Shipment every %s', 'flexible-subscriptions' ),
						$candidate->get_billing_frequency()->to_readable_string()
					);

					$selections[] = new RecurringShippingPackage(
						$package_key,
						$recurring_rates,
						$selected_method,
						$package_details,
						$package,
						$match_initial_rates
					);
				}

				return $selections;
			}
		);
	}

	/**
	 * @param array<string, mixed>|false $shipping
	 * @return array<string, \WC_Shipping_Rate>
	 */
	private function extract_rates( $shipping ): array {
		$rates = is_array( $shipping ) ? ( $shipping['rates'] ?? [] ) : [];

		return is_array( $rates ) ? $rates : [];
	}

	private function rates_match_initial_package( array $original_packages, string $package_key, array $recurring_rates ): bool {
		$package_index = RecurringShippingPackage::index_from_key( $package_key );
		if ( $package_index === null ) {
			return false;
		}

		if ( ! isset( $original_packages[ $package_index ]['rates'] ) ) {
			return false;
		}

		$original_rates = $original_packages[ $package_index ]['rates'];
		if ( count( $original_rates ) !== count( $recurring_rates ) ) {
			return false;
		}

		$original_index = [];
		foreach ( $original_rates as $original_rate ) {
			$key                    = $this->rate_signature( $original_rate );
			$original_index[ $key ] = ( $original_index[ $key ] ?? 0 ) + 1;
		}

		foreach ( $recurring_rates as $recurring_rate ) {
			$key = $this->rate_signature( $recurring_rate );
			if ( empty( $original_index[ $key ] ) ) {
				return false;
			}
			--$original_index[ $key ];
		}

		return true;
	}

	private function rate_signature( \WC_Shipping_Rate $rate ): string {
		$taxes = (array) $rate->get_taxes();
		return implode(
			'|',
			[
				$rate->get_method_id(),
				(string) $rate->get_instance_id(),
				(string) $rate->get_cost(),
				(string) array_sum( $taxes ),
				(string) $rate->get_label(),
			]
		);
	}
}
