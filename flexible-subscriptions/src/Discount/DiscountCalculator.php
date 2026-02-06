<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

interface DiscountCalculator {

	public function discount_amount( float $discounting_amount, \WC_Coupon $coupon, DiscountingItem $item ): float;
}
