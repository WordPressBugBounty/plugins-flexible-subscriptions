<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class RegisteredUser implements Constraint {

	public function validate( Subscription $subscription ): ?ConstraintViolation {
		if ( $subscription->get_user() instanceof \WP_User ) {
			return null;
		}

		return new ConstraintViolation( 'A valid subscription requires a registered user.' );
	}
}
