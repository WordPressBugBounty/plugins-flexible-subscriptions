<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

class RecurringPercentDiscount implements DiscountCalculator {

	public function discount_amount( float $discounting_amount, \WC_Coupon $coupon, DiscountingItem $item ): float {
		if ( ! $item->is_singular() ) {
			$discounting_amount = $discounting_amount * $item->get_quantity();
		}

		return ( $discounting_amount / 100 ) * $coupon->get_amount();
	}
}
