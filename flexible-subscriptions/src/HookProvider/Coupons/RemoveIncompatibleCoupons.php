<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Coupons;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class RemoveIncompatibleCoupons implements HookProvider {
	public function hooks(): void {
		add_action( 'woocommerce_before_calculate_totals', $this, 10 );
	}

	/**
	 * @param \WC_Cart $cart
	 */
	public function __invoke( $cart ): void {
		// Only hook when totals are being calculated completely (on cart & checkout pages).
		if (
			! WC_Subscriptions_Cart::cart_contains_subscription() ||
			empty( $cart->recurring_cart_key )
		) {
			return;
		}

		foreach ( $cart->get_applied_coupons() as $coupon_code ) {
			$coupon      = new \WC_Coupon( $coupon_code );
			$coupon_type = $coupon->get_discount_type();

			if ( ! isset( self::$recurring_coupons[ $coupon_type ] ) ) {
				$cart->remove_coupon( $coupon_code );
				continue;
			}

			if ( 'recurring_total' === $calculation_type || ! WC_Subscriptions_Cart::all_cart_items_have_free_trial() ) {
				continue;
			}

			$cart->remove_coupon( $coupon_code );
		}
	}
}
