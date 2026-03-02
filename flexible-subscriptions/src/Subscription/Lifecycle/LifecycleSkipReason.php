<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Lifecycle;

/**
 * Structured reason codes for subscription lifecycle skip/failure paths.
 *
 * Used as `skip_reason` values in log context to enable deterministic support debugging.
 */
final class LifecycleSkipReason {

	// PaymentRequestProcessor skip reasons.
	public const SUBSCRIPTION_NOT_ACTIVE            = 'subscription_not_active';
	public const SUBSCRIPTION_EXPIRED               = 'subscription_expired';
	public const RENEWAL_INTERRUPTED_BY_INTEGRATION = 'renewal_interrupted_by_integration';
	public const RENEWAL_LOCK_NOT_ACQUIRED          = 'renewal_lock_not_acquired';

	// SubscriptionLifecycleManager::process_paid_renewal skip reasons.
	public const RENEWAL_NOT_LATEST_PAYMENT_REQUEST = 'renewal_not_latest_payment_request';
	public const RENEWAL_PERIOD_ALREADY_ADVANCED    = 'renewal_period_already_advanced';
	public const RENEWAL_PERIOD_ADVANCE_FAILED      = 'renewal_period_advance_failed';
}
