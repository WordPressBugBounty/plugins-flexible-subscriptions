<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Events;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

final class BillingFrequencyUpdated {

	public Subscription $subscription;

	public WPInterval $new_frequency;

	public WPInterval $old_frequency;

	public function __construct( Subscription $param, WPInterval $frequency, WPInterval $old ) {
		$this->subscription  = $param;
		$this->new_frequency = $frequency;
		$this->old_frequency = $old;
	}
}
