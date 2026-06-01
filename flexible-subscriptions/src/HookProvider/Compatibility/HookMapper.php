<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Compatibility;

use WC_Subscription;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Compatibility\Caster;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Map Flexible Subscriptions hooks to mirrored WooCommerce Subscriptions hooks for compatibility with external plugins.
 */
final class HookMapper implements HookProvider {

	private Caster $caster;

	public function __construct( Caster $caster ) {
		$this->caster = $caster;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/new', [ $this, 'on_subscription_created' ], 10, 3 );
		add_action( 'fsub/subscription_renewal/created', [ $this, 'on_subscription_renewal_created' ], 10, 2 );
		add_action( 'fsub/subscription/payment_request/pay', [ $this, 'on_scheduled_payment' ], 10, 1 );
		add_action( 'fsub/subscription/payment_request/updated_failing_payment_method', [ $this, 'on_updated_failing_payment_method' ], 10, 2 );
		add_action( 'fsub/subscription/validate_payment_meta', [ $this, 'validate_payment_meta' ], 10, 3 );
		add_action( 'woocommerce_fsb-variable-subscription_add_to_cart', [ $this, 'variation_add_to_cart_template' ] );
		\add_action( 'woocommerce_fsb-subscription_add_to_cart', [ $this, 'subscription_add_to_cart_template' ] );
	}

	public function on_subscription_created( $subscription, $order, $candidate ): void {
		$cart = $candidate instanceof SubscriptionCandidate ? $candidate->get_cart() : null;

		do_action( 'woocommerce_checkout_subscription_created', $this->caster->cast_to( \WC_Subscription::class, $subscription ), $order, $cart );
	}

	public function on_subscription_renewal_created( $payment_request, $subscription ): void {
		// TODO: only run if WCS compatibility loaded.
		apply_filters( 'wcs_renewal_order_created', $payment_request, $this->caster->cast_to( WC_Subscription::class, $subscription ) );
	}

	public function on_scheduled_payment( $payment_request ): void {
		do_action( 'woocommerce_scheduled_subscription_payment_' . $payment_request->get_payment_method(), $payment_request->get_total(), $payment_request );
	}

	public function on_updated_failing_payment_method( $subscription, $order ): void {
		$renewal_order = $order instanceof Renewal ? $order->get_order() : $order;
		$gateway_id    = $renewal_order->get_payment_method();

		if ( $gateway_id ) {
			do_action(
				'woocommerce_subscription_failing_payment_method_updated_' . $gateway_id,
				$this->caster->cast_to( WC_Subscription::class, $subscription ),
				$renewal_order
			);
		}
	}

	/**
	 * @param array        $post_data
	 * @param Subscription $subscription
	 * @param string       $payment_method
	 */
	public function validate_payment_meta( $post_data, $subscription, $payment_method ): void {
		$cast_subscription = $this->caster->cast_to( WC_Subscription::class, $subscription );
		$payment_meta      = $this->prepare_payment_meta_for_validation( (array) $post_data, $cast_subscription, $payment_method );

		do_action( 'woocommerce_subscription_validate_payment_meta', $payment_method, $payment_meta, $cast_subscription );
		do_action(
			'woocommerce_subscription_validate_payment_meta_' . $payment_method,
			$payment_meta,
			$cast_subscription
		);
	}

	/**
	 * Build WCS-like payment meta payload for validation callbacks.
	 *
	 * @param array<string, mixed> $post_data
	 * @return array<string, mixed>
	 */
	private function prepare_payment_meta_for_validation( array $post_data, WC_Subscription $subscription, string $payment_method ): array {
		$payment_meta = apply_filters( 'woocommerce_subscription_payment_meta', [], $subscription );
		$payment_meta = (array) ( $payment_meta[ $payment_method ] ?? [] );

		$post_meta = (array) ( $payment_meta['post_meta'] ?? [] );
		foreach ( $post_meta as $meta_key => $meta_config ) {
			if ( array_key_exists( $meta_key, $post_data ) ) {
				$post_meta[ $meta_key ]['value'] = sanitize_text_field( wp_unslash( (string) $post_data[ $meta_key ] ) );
			}
		}
		$payment_meta['post_meta'] = $post_meta;

		return $payment_meta;
	}

	public function variation_add_to_cart_template(): void {
		do_action( 'woocommmerce_variable-subscription_add_to_cart' );
	}

	public function subscription_add_to_cart_template(): void {
		do_action( 'woocommmerce_subscription_add_to_cart' );
	}
}
