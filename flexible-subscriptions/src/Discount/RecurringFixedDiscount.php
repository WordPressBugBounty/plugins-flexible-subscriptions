<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

class RecurringFixedDiscount implements DiscountCalculator {

	public function discount_amount( float $discounting_amount, \WC_Coupon $coupon, DiscountingItem $item ): float {
		// We can discount sequentially (based on WC settings), so make sure to take the lowest value as the base.
		$discounting_amount = min( (float) $coupon->get_amount(), $discounting_amount );
		if ( ! $item->is_singular() ) {
			$discounting_amount = $discounting_amount * $item->get_quantity();
		}

		return $discounting_amount;
	}
}
