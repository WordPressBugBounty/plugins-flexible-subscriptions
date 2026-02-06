<?php

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

trait SubscriptionHashCalculatorTrait {

	private function calculate_hash( SubscriptionProductWrapper $product ): string {
		return md5( $product->get_billing_frequency() . $product->get_trial_duration() . $product->get_expiration() );
	}
}
