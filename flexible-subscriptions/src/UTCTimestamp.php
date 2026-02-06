<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions;

/**
 * Always represent unix timestamp in UTC timezone.
 */
class UTCTimestamp {

	private string $date;

	public function __construct( string $date ) {
		$this->date = $date;
	}

	public function timestamp(): int {
		return ( new \DateTimeImmutable( $this->date, new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	}
}
