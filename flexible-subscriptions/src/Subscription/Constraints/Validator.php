<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

/**
 * Validation of subscription entity is tricky right now. Preferably, validation should be triggered
 * right before saving of a subscription, preventing database from writing invalid and incomplete
 * data. Unfortunately, in WooCommerce an entity save is flushed a few times during a single request,
 * and some of the saves are out of our control (saving subscription items flushes saving of the
 * whole subscription object on admin page).
 *
 * The best solution is to validate subscription by clients, which will later determine whether to
 * display any errors or ignore them (e.g. admin subscription update validates before final save and
 * decides to display a proper notice). This also implies one important concept about the whole
 * system: subscriptions always have to be operational, no matter what data is missing.
 */
class Validator {

	/** @var Constraint[] */
	private array $constraints;

	/** @param Constraint[] $constraints */
	public function __construct( array $constraints ) {
		$this->constraints = $constraints;
	}

	public function validate( Subscription $subscription ): ConstraintViolationList {
		$errors = new ConstraintViolationList();

		foreach ( $this->constraints as $constraint ) {
			$violation = $constraint->validate( $subscription );
			if ( $violation !== null ) {
				$errors->add( $violation );
			}
		}

		return $errors;
	}
}
