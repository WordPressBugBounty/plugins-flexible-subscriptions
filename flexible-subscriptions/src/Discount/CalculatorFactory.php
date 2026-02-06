<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

class CalculatorFactory {

	public function get_calculator( \WC_Coupon $coupon ): DiscountCalculator {
		switch ( $coupon->get_discount_type() ) {
			case 'recurring_percent':
				return new RecurringPercentDiscount();
			case 'recurring_fee':
				return new RecurringFixedDiscount();
			case 'sign_up_fee':
				return new SignupFeeFixedDiscount();
			case 'sign_up_fee_percent':
				return new SignupFeePercentDiscount();
			default:
				return new NullDiscount();
		}
	}
}
