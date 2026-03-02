<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Events;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

final class SubscriptionPaused {

	public function __construct( public Subscription $subscription ) {}
}
