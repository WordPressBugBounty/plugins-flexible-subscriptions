<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Events;

use DateTimeImmutable;
use DateTimeInterface;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

final class TrialEndDateUpdated {

	public Subscription $subscription;

	public ?DateTimeImmutable $date;

	public ?DateTimeInterface $old_date;

	public function __construct( Subscription $param, ?DateTimeImmutable $date, ?DateTimeInterface $old_date ) {
		$this->subscription = $param;
		$this->date         = $date;
		$this->old_date     = $old_date;
	}
}
