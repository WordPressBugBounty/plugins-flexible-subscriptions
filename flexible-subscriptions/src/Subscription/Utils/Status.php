<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Utils;

/**
 * Enum-like class for subscription statuses.
 */
final class Status {
	public const ACTIVE         = 'wc-active';
	public const PENDING        = 'wc-pending';
	public const ON_HOLD        = 'wc-on-hold';
	public const CANCELLED      = 'wc-cancelled';
	public const SWITCHED       = 'wc-switched';
	public const EXPIRED        = 'wc-expired';
	public const PENDING_CANCEL = 'wc-pending-cancel';
	public const TRASH          = 'wc-trash';

	/** @return array<string, string> */
	public static function get_statuses(): array {
		return [
			self::ACTIVE         => _x( 'Active', 'Subscription status', 'flexible-subscriptions' ),
			self::PENDING        => _x( 'Pending', 'Subscription status', 'flexible-subscriptions' ),
			self::ON_HOLD        => _x( 'On hold', 'Subscription status', 'flexible-subscriptions' ),
			self::CANCELLED      => _x( 'Cancelled', 'Subscription status', 'flexible-subscriptions' ),
			self::SWITCHED       => _x( 'Switched', 'Subscription status', 'flexible-subscriptions' ),
			self::EXPIRED        => _x( 'Expired', 'Subscription status', 'flexible-subscriptions' ),
			self::PENDING_CANCEL => _x( 'Pending Cancellation', 'Subscription status', 'flexible-subscriptions' ),
			self::TRASH          => _x( 'Trashed', 'Subscription status', 'flexible-subscriptions' ),
		];
	}

	public static function nice_name( string $status ): string {
		if ( ! str_starts_with( 'wc-', $status ) ) {
			$status = 'wc-' . $status;
		}
		return self::get_statuses()[ $status ] ?? _x( 'Unknown', 'Subscription status', 'flexible-subscriptions' );
	}
}
