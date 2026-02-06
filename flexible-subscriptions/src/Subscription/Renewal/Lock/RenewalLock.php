<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock;

interface RenewalLock {

	/**
	 * @return string|null Returns lock owner token if acquired, null otherwise.
	 */
	public function acquire( int $subscription_id, int $ttl_seconds = 600 ): ?string;

	public function release( int $subscription_id, string $owner ): void;
}
