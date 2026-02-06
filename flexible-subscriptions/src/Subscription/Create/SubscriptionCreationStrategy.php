<?php

namespace WPDesk\FlexibleSubscriptions\Subscription\Create;

use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

interface SubscriptionCreationStrategy {
	public function apply_totals( Subscription $subscription ): void;

	public function add_auxiliary_lines( Subscription $subscription, \WC_Order $parent_order ): void;

	public function add_line_items( Subscription $subscription ): void;

	public function requires_manual_renewal( \WC_Order $parent_order, PaymentMethodSeeker $seeker ): bool;
}
