<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

class ConstraintViolation {

	private string $message;

	public function __construct( string $message ) {
		$this->message = $message;
	}

	public function __toString(): string {
		return $this->message;
	}
}
