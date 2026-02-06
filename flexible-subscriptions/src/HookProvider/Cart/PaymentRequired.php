<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Verify if cart payment is required for multiple cases, like
 * subscription with free trial.
 */
class PaymentRequired implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	public function __construct( SubscriptionCandidatesList $candidates ) {
		$this->candidates = $candidates;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_cart_needs_payment', $this, 10, 2 );
	}

	/**
	 * @param bool $needs_payment
	 *
	 * @param \WC_Cart $cart
	 * @return bool
	 */
	public function __invoke( $needs_payment, $cart ) {
		if ( $needs_payment === true || ( (int) $cart->get_total( 'edit' ) ) > 0 ) {
			return $needs_payment;
		}

		if ( count( $this->candidates ) === 0 ) {
			return $needs_payment;
		}

		$total = array_reduce(
			iterator_to_array( $this->candidates ),
			function ( $tot, SubscriptionCandidate $item ) {
				if ( $item->has_trial() ) {
					return $tot;
				}
				return $tot + $item->get_subtotal();
			},
			0
		);

		if ( $total === 0 ) {
			$force_payment = (bool) apply_filters( 'fsub/cart/require_payment_on_trial', false );
			if ( $force_payment ) {
				// We are still in early stage of development, so excuse this ugly hack.
				// It is logically correct, when admin force requires payment on zero total, we
				// ensure it is handled with automatic payments. The whole purpose is to intercept
				// customer payment details for future processing.
				add_filter( 'fsub/payment/manual_renewal/enabled', '__return_false' );
			}
			return $force_payment;
		}

		return $total > 0;
	}
}
