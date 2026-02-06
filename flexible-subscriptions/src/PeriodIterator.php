<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions;

/**
 * @implements \IteratorAggregate<string, string>
 */
class PeriodIterator implements \IteratorAggregate {
	public const DAY   = 'D';
	public const WEEK  = 'W';
	public const MONTH = 'M';
	public const YEAR  = 'Y';

	public function getIterator(): \Traversable {
		return new \ArrayIterator( $this->get_periods() );
	}

	/**
	 * @return array<string, string>
	 */
	private function get_periods(): array {
		return [
			self::DAY   => _x( 'day(s)', 'period', 'flexible-subscriptions' ),
			self::WEEK  => _x( 'week(s)', 'period', 'flexible-subscriptions' ),
			self::MONTH => _x( 'month(s)', 'period', 'flexible-subscriptions' ),
			self::YEAR  => _x( 'year(s)', 'period', 'flexible-subscriptions' ),
		];
	}
}
