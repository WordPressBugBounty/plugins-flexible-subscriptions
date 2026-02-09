<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Model;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class SubscriptionPlan {

	private WPInterval $billing_frequency;

	private ?WPInterval $trial_period = null;

	private ?WPInterval $expiration = null;

	public function __construct( WPInterval $billing_frequency, ?WPInterval $trial_period = null, ?WPInterval $expiration = null ) {
		$this->billing_frequency = $billing_frequency;
		$this->trial_period      = $trial_period;
		$this->expiration        = $expiration;
	}

	public function get_billing_frequency(): WPInterval {
		return $this->billing_frequency;
	}

	public function get_trial_period(): ?WPInterval {
		return $this->trial_period;
	}

	public function get_expiration(): ?WPInterval {
		return $this->expiration;
	}

	public function get_hash(): string {
		return md5(
			$this->billing_frequency .
			$this->trial_period .
			$this->expiration
		);
	}

	public function equals( SubscriptionPlan $other ): bool {
		return $this->get_hash() === $other->get_hash();
	}

	public function is_one_time(): bool {
		return $this->billing_frequency->equalTo( $this->expiration );
	}
}
