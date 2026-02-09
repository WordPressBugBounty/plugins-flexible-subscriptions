<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal;

final class RecurringCouponFilter {

	private const ALLOWED_DISCOUNT_TYPES = [
		'recurring_fee',
		'recurring_percent',
	];

	public function is_compatible( string $coupon_code ): bool {
		$coupon = new \WC_Coupon( $coupon_code );

		if ( ! in_array( $coupon->get_discount_type(), self::ALLOWED_DISCOUNT_TYPES, true ) ) {
			return false;
		}

		return true;
	}
}
