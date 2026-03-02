<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Events;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

final class StatusUpdated {

	public Subscription $subscription;

	public string $new_status;

	public string $old_status;

	public function __construct( Subscription $param, string $new_status, string $old_status ) {
		$this->subscription = $param;
		$this->new_status   = $new_status;
		$this->old_status   = $old_status;
	}
}
