<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

class DateTimeUtils {

	/**
	 * Get current UTC date.
	 */
	public static function now(): \DateTimeInterface {
		return new \DateTimeImmutable( gmdate( 'Y-m-d H:i:s' ), new \DateTimeZone( 'UTC' ) );
	}
}
