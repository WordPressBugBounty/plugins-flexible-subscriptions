<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

class SignupFeePercentDiscount implements DiscountCalculator {

	public function discount_amount( float $discounting_amount, \WC_Coupon $coupon, DiscountingItem $item ): float {
		$product = new SubscriptionProductWrapper( $item->get_product() );
		if ( wc_prices_include_tax() ) {
			$signup_fee = wc_get_price_including_tax(
				$item->get_product(),
				[
					'qty'   => 1,
					'price' => $product->get_signup_fee(),
				]
			);
		} else {
			$signup_fee = wc_get_price_excluding_tax(
				$item->get_product(),
				[
					'qty'   => 1,
					'price' => $product->get_signup_fee(),
				]
			);
		}

		if ( ! $item->is_singular() ) {
			$signup_fee *= $item->get_quantity();
		}

		return ( $signup_fee * $coupon->get_amount() ) / 100;
	}
}
