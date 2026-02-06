<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

/**
 * @extends \DatePeriod<\DateTime, ?\DateTime, 0>
 */
class NullPeriod extends \DatePeriod {

	public function __construct() {
		parent::__construct(
			new \DateTime( '1970-01-01 00:00:00' ),
			new \DateInterval( 'PT0S' ),
			new \DateTime( '1970-01-01 00:00:00' )
		);
	}

	public function getEndDate(): ?\DateTimeInterface {
		return null;
	}
}
