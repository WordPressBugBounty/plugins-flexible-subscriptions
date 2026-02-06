<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class MatchingParentOrder implements Constraint {

	public function validate( Subscription $subscription ): ?ConstraintViolation {
		$user = $subscription->get_user();
		// This is checked in RegisteredUser constraint.
		if ( ! $user instanceof \WP_User ) {
			return null;
		}

		$parent_order = $subscription->get_parent();

		if ( $parent_order instanceof \WC_Order ) {
			$parent_user = $parent_order->get_user();

			if ( $parent_user != $user ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- weak comparison on purpose, as object may have different handler.
				return new ConstraintViolation( 'Subscription user does not match parent order user.' );
			}
		}

		return null;
	}
}
