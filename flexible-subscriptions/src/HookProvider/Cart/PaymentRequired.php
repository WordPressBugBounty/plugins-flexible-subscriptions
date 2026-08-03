<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Settings\PaymentOptions;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Verify if a payment method is required for a zero-total initial order.
 */
class PaymentRequired implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private PaymentOptions $payment_options;

	public function __construct( SubscriptionCandidatesList $candidates, PaymentOptions $payment_options ) {
		$this->candidates      = $candidates;
		$this->payment_options = $payment_options;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_cart_needs_payment', $this, 10, 2 );
		add_filter( 'woocommerce_order_needs_payment', [ $this, 'order_needs_payment' ], 10, 3 );
	}

	/**
	 * @param bool $needs_payment
	 *
	 * @param \WC_Cart $cart
	 * @return bool
	 */
	public function __invoke( $needs_payment, $cart ) {
		if ( $needs_payment === true || ( (float) $cart->get_total( 'edit' ) ) > 0 ) {
			return $needs_payment;
		}

		return $this->payment_required_for_zero_total( $needs_payment );
	}

	/**
	 * Store API checks the order instead of applying woocommerce_cart_needs_payment
	 * before processing the selected gateway.
	 *
	 * @param bool      $needs_payment
	 * @param \WC_Order $order
	 * @param string[]  $valid_order_statuses
	 * @return bool
	 */
	public function order_needs_payment( $needs_payment, $order, $valid_order_statuses ): bool {
		if (
			$needs_payment === true ||
			! $order instanceof \WC_Order ||
			! $order->has_status( $valid_order_statuses ) ||
			( (float) $order->get_total() ) > 0
		) {
			return (bool) $needs_payment;
		}

		return $this->payment_required_for_zero_total( $needs_payment );
	}

	/**
	 * @param bool $needs_payment
	 */
	private function payment_required_for_zero_total( $needs_payment ): bool {
		if ( count( $this->candidates ) === 0 ) {
			return $needs_payment;
		}

		$recurring_total = array_reduce(
			iterator_to_array( $this->candidates ),
			static fn ( float $total, SubscriptionCandidate $candidate ): float => $total + $candidate->get_total(),
			0.0
		);

		if ( $recurring_total <= 0.0 ) {
			return false;
		}

		// The filter name is retained for backward compatibility.
		$force_payment = (bool) apply_filters(
			'fsub/cart/require_payment_on_trial',
			$this->payment_options->require_payment_on_zero_total()
		);
		if ( $force_payment ) {
			// A reusable payment method requires a gateway supporting automatic renewals.
			add_filter( 'fsub/payment/manual_renewal/enabled', '__return_false' );
		}

		return $force_payment;
	}
}
