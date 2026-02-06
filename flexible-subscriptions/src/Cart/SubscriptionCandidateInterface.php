<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

interface SubscriptionCandidateInterface {
	public function get_billing_frequency(): WPInterval;

	public function get_start_date(): \DateTimeInterface;

	public function get_trial_end_date(): ?\DateTimeInterface;

	public function get_expiration(): ?WPInterval;

	public function get_created_via(): string;
}
