<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

interface Constraint {

	public function validate( Subscription $subscription ): ?ConstraintViolation;
}
