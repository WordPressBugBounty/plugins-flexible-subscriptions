<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Settings\PaymentOptions;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\RecurringShippingResolver;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class ExtendStoreApi implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private RecurringShippingResolver $shipping_resolver;

	private PaymentOptions $payment_options;

	public function __construct( SubscriptionCandidatesList $candidates, RecurringShippingResolver $shipping_resolver, PaymentOptions $payment_options ) {
		$this->candidates        = $candidates;
		$this->shipping_resolver = $shipping_resolver;
		$this->payment_options   = $payment_options;
	}

	public function hooks(): void {
		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_endpoints' ] );
	}

	public function register_endpoints(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			[
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => 'flexibleSubscriptions',
				'data_callback'   => [ $this, 'get_cart_item_data' ],
				'schema_callback' => [ $this, 'get_cart_item_schema' ],
			]
		);

		woocommerce_store_api_register_endpoint_data(
			[
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'flexibleSubscriptions',
				'data_callback'   => [ $this, 'get_cart_data' ],
				'schema_callback' => [ $this, 'get_cart_schema' ],
			]
		);

		woocommerce_store_api_register_payment_requirements(
			[
				'data_callback' => [ $this, 'get_payment_requirements' ],
			]
		);
	}

	/**
	 * @param array $cart_item
	 * @return array
	 */
	public function get_cart_item_data( array $cart_item ): array {
		$product = $cart_item['data'];
		if ( ! $product instanceof SubscriptionProduct ) {
			return [];
		}

		$wrapper = new SubscriptionProductWrapper( $product );

		return [
			'billing_period'   => $wrapper->get_payment_period(),
			'billing_interval' => $wrapper->get_payment_interval(),
			'trial_length'     => $wrapper->get_trial_length(),
			'trial_period'     => $wrapper->get_trial_period(),
			'signup_fee'       => $wrapper->get_signup_fee(),
		];
	}

	/**
	 * @return array
	 */
	public function get_cart_item_schema(): array {
		return [
			'billing_period'   => [
				'description' => __( 'Billing period for the subscription.', 'flexible-subscriptions' ),
				'type'        => [ 'string', 'null' ],
				'readonly'    => true,
			],
			'billing_interval' => [
				'description' => __( 'The number of billing periods between subscription renewals.', 'flexible-subscriptions' ),
				'type'        => [ 'integer', 'null' ],
				'readonly'    => true,
			],
			'trial_length'     => [
				'description' => __( 'Subscription Product trial length.', 'flexible-subscriptions' ),
				'type'        => [ 'integer', 'null' ],
				'readonly'    => true,
			],
			'trial_period'     => [
				'description' => __( 'Subscription Product trial period.', 'flexible-subscriptions' ),
				'type'        => [ 'string', 'null' ],
				'readonly'    => true,
			],
			'signup_fee'       => [
				'description' => __( 'Subscription Product sign-up fee.', 'flexible-subscriptions' ),
				'type'        => [ 'string', 'null' ],
				'readonly'    => true,
			],
		];
	}

	/**
	 * @return array
	 */
	public function get_cart_data(): array {
		if ( count( $this->candidates ) === 0 ) {
			return [];
		}

		$money_formatter      = woocommerce_store_api_get_formatter( 'money' );
		$currency_formatter   = woocommerce_store_api_get_formatter( 'currency' );
		$html_formatter       = woocommerce_store_api_get_formatter( 'html' );
		$future_subscriptions = [];

		foreach ( $this->candidates as $candidate ) {
			$billing_frequency = $candidate->get_billing_frequency();
			$expiration        = $candidate->get_expiration();
			$first_payment     = $candidate->get_first_payment_date();

			$future_subscriptions[] = [
				'key'                          => $candidate->get_group(),
				'first_payment_date'           => wp_date( get_option( 'date_format' ), $first_payment->getTimestamp() ),
				'billing_frequency_readable'   => $billing_frequency->to_readable_string(),
				'expiration_readable'          => $expiration ? $expiration->to_readable_string() : '',
				'totals'                       => $currency_formatter->format(
					[
						'total_items'        => $money_formatter->format( $candidate->get_subtotal() ),
						'total_items_tax'    => $money_formatter->format( $candidate->get_cart_contents_tax() ),
						'total_fees'         => $money_formatter->format( $candidate->get_fee_total() ),
						'total_fees_tax'     => $money_formatter->format( $candidate->get_fee_tax() ),
						'total_discount'     => $money_formatter->format( $candidate->get_discount_total() ),
						'total_discount_tax' => $money_formatter->format( $candidate->get_discount_tax() ),
						'total_shipping'     => $money_formatter->format( $candidate->get_shipping_total() ),
						'total_shipping_tax' => $money_formatter->format( $candidate->get_shipping_tax() ),
						'total_price'        => $money_formatter->format( $candidate->get_total() ),
						'total_tax'          => $money_formatter->format( $candidate->get_total_tax() ),
						'tax_lines'          => $this->get_tax_lines( $candidate->get_cart() ),
					]
				),
				'shipping_rates'               => $this->get_shipping_packages(
					$candidate,
					$money_formatter,
					$currency_formatter,
					$html_formatter
				),
			];
		}

		return [ 'future_subscriptions' => $future_subscriptions ];
	}

	/**
	 * @return array
	 */
	public function get_cart_schema(): array {
		return [
			'future_subscriptions' => [
				'description' => __( 'A list of recurring totals for subscriptions in the cart.', 'flexible-subscriptions' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'key'                          => [ 'type' => 'string' ],
						'first_payment_date'           => [
							'description' => __( 'Localized first renewal date for the subscription.', 'flexible-subscriptions' ),
							'type'        => 'string',
							'readonly'    => true,
						],
						'billing_frequency_readable'   => [
							'description' => __( 'Human-readable billing frequency for the subscription.', 'flexible-subscriptions' ),
							'type'        => 'string',
							'readonly'    => true,
						],
						'expiration_readable'          => [
							'description' => __( 'Human-readable expiration for the subscription.', 'flexible-subscriptions' ),
							'type'        => 'string',
							'readonly'    => true,
						],
						'totals'                       => [ 'type' => 'object' ],
						'shipping_rates'               => [
							'description' => __( 'Recurring shipping packages with rates.', 'flexible-subscriptions' ),
							'type'        => 'array',
							'readonly'    => true,
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'package_id'          => [ 'type' => [ 'string', 'integer' ] ],
									'name'                => [ 'type' => 'string' ],
									'destination'         => [ 'type' => 'object' ],
									'items'               => [ 'type' => 'array' ],
									'package_details'     => [ 'type' => 'string' ],
									'needs_shipping'      => [ 'type' => 'boolean' ],
									'match_initial_rates' => [ 'type' => 'boolean' ],
									'shipping_rates'      => [ 'type' => 'array' ],
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @return string[]
	 */
	public function get_payment_requirements(): array {
		if (
			count( $this->candidates ) === 0 ||
			apply_filters(
				'fsub/payment/manual_renewal/enabled',
				$this->payment_options->manual_renewal_enabled()
			)
		) {
			return [];
		}

		return count( $this->candidates ) > 1 ? [ 'multiple_subscriptions' ] : [ 'subscriptions' ];
	}

	/**
	 * @param \WC_Cart $cart
	 * @return array
	 */
	private function get_tax_lines( \WC_Cart $cart ): array {
		$money_formatter = woocommerce_store_api_get_formatter( 'money' );
		$tax_lines       = [];
		foreach ( $cart->get_tax_totals() as $tax ) {
			$tax_lines[] = [
				'name'  => $tax->label,
				'price' => $money_formatter->format( $tax->amount ),
			];
		}
		return $tax_lines;
	}

	/**
	 * @param \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
	 * @param object $money_formatter
	 * @param object $currency_formatter
	 * @param object $html_formatter
	 * @return array
	 */
	private function get_shipping_packages( SubscriptionCandidate $candidate, $money_formatter, $currency_formatter, $html_formatter ): array {
		$packages = [];

		foreach ( $this->shipping_resolver->resolve( $candidate ) as $package_data ) {
			$package     = $package_data->get_package();
			$package_key = $package_data->get_package_key();

			$packages[] = [
				'package_id'          => $package['package_id'] ?? $package_key,
				'name'                => $html_formatter->format( $package['package_name'] ?? '' ),
				'destination'         => $this->get_package_destination( $package, $html_formatter ),
				'items'               => $this->get_package_items( $package ),
				'package_details'     => $html_formatter->format( $package_data->get_package_details() ),
				'needs_shipping'      => ! empty( $package['contents'] ),
				'match_initial_rates' => $package_data->match_initial_rates(),
				'shipping_rates'      => $this->get_package_shipping_rates(
					$package_data->get_rates(),
					$package_data->get_selected_method(),
					$money_formatter,
					$currency_formatter,
					$html_formatter
				),
			];
		}

		return $packages;
	}

	/**
	 * @param array $package
	 * @param object $html_formatter
	 * @return object
	 */
	private function get_package_destination( array $package, $html_formatter ): object {
		$destination = $package['destination'] ?? [];
		$address     = $destination['address_1'] ?? ( $destination['address'] ?? '' );

		return (object) $html_formatter->format(
			[
				'address_1' => $address,
				'address_2' => $destination['address_2'] ?? '',
				'city'      => $destination['city'] ?? '',
				'state'     => $destination['state'] ?? '',
				'postcode'  => $destination['postcode'] ?? '',
				'country'   => $destination['country'] ?? '',
			]
		);
	}

	/**
	 * @param array $package
	 * @return array
	 */
	private function get_package_items( array $package ): array {
		$items = [];
		foreach ( $package['contents'] ?? [] as $values ) {
			$items[] = [
				'key'      => $values['key'] ?? '',
				'name'     => $values['data']->get_name(),
				'quantity' => $values['quantity'],
			];
		}

		return $items;
	}

	/**
	 * @param array<string, \WC_Shipping_Rate> $rates
	 * @param string $selected_method
	 * @param object $money_formatter
	 * @param object $currency_formatter
	 * @param object $html_formatter
	 * @return array
	 */
	private function get_package_shipping_rates( array $rates, string $selected_method, $money_formatter, $currency_formatter, $html_formatter ): array {
		$response = [];

		foreach ( $rates as $rate ) {
			$rate_data = [
				'rate_id'       => $rate->get_id(),
				'name'          => $html_formatter->format( $rate->get_label() ),
				'description'   => $html_formatter->format( $rate->get_description() ),
				'delivery_time' => $html_formatter->format( $rate->get_delivery_time() ),
				'price'         => $money_formatter->format( $rate->get_cost() ),
				'taxes'         => $money_formatter->format( array_sum( (array) $rate->get_taxes() ) ),
				'method_id'     => $rate->get_method_id(),
				'instance_id'   => $rate->get_instance_id(),
				'meta_data'     => $this->get_rate_meta_data( $rate ),
				'selected'      => $selected_method === $rate->get_id(),
			];

			$response[] = $currency_formatter->format( $rate_data );
		}

		return $response;
	}

	/**
	 * @param \WC_Shipping_Rate $rate
	 * @return array
	 */
	private function get_rate_meta_data( \WC_Shipping_Rate $rate ): array {
		$meta_data = $rate->get_meta_data();

		return array_reduce(
			array_keys( $meta_data ),
			function ( array $return, $key ) use ( $meta_data ): array {
				$return[] = [
					'key'   => $key,
					'value' => $meta_data[ $key ],
				];
				return $return;
			},
			[]
		);
	}
}
