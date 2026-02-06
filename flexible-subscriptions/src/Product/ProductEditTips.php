<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Product;

/**
 * A sack for static methods with extra descriptions to product edit screen for a reseller. Those methods allow simple access and are translation ready.
 */
final class ProductEditTips {

	public static function expiration(): string {
		return __(
			'Automatically expire the subscription after this length of time. This length is in addition to any free trial or amount of time provided before a synchronised first renewal date.',
			'flexible-subscriptions'
		);
	}

	public static function free_trial(): string {
		return _x(
			'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription.',
			'Trial period field tooltip on Edit Product administration screen',
			'flexible-subscriptions'
		);
	}

	public static function payment_frequency(): string {
		return __( 'Choose the subscription price, billing interval and period.', 'flexible-subscriptions' );
	}

	public static function sign_up_fee(): string {
		return __( 'Optionally include an amount to be charged at the outset of the subscription. The sign-up fee will be charged immediately, even if the product has a free trial or the payment dates are synced.', 'flexible-subscriptions' );
	}

	public static function one_time_shipping(): string {
		return __( 'Shipping for subscription products is normally charged on the initial order and all renewal orders. Enable this to only charge shipping once on the initial order. Note: for this setting to be enabled the subscription must not have a free trial or a synced renewal date.', 'flexible-subscriptions' );
	}
}
