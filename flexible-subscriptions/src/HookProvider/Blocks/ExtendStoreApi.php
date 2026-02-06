<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class ExtendStoreApi implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	public function __construct( SubscriptionCandidatesList $candidates ) {
		$this->candidates = $candidates;
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
		$future_subscriptions = [];

		foreach ( $this->candidates as $candidate ) {
			$billing_frequency = $candidate->get_billing_frequency();
			$expiration        = $candidate->get_expiration();

			$future_subscriptions[] = [
				'key'                          => $candidate->get_group(),
				'first_payment_date'           => $candidate->get_first_payment_date()->format( 'Y-m-d H:i:s' ),
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
							'type'   => 'string',
							'format' => 'date-time',
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
					],
				],
			],
		];
	}

	/**
	 * @return string[]
	 */
	public function get_payment_requirements(): array {
		return count( $this->candidates ) > 0 ? [ 'subscriptions' ] : [];
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
}
