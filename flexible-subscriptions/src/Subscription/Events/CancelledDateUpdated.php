<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Events;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

final class CancelledDateUpdated {

	public Subscription $subscription;

	public ?\DateTimeImmutable $new_date;

	public ?\DateTimeInterface $old_date;

	public function __construct( Subscription $subscription, ?\DateTimeImmutable $new_date, ?\DateTimeInterface $old_date ) {
		$this->subscription = $subscription;
		$this->new_date     = $new_date;
		$this->old_date     = $old_date;
	}
}
