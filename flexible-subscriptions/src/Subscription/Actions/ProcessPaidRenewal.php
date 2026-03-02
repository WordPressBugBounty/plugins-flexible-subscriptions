<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Subscription\Actions;

use WPDesk\FlexibleSubscriptions\Subscription\BillingPeriodCalculator;
use WPDesk\FlexibleSubscriptions\Subscription\Lifecycle\LifecycleSkipReason;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use const MINUTE_IN_SECONDS;

final class ProcessPaidRenewal {

	private SubscriptionRepository $repository;

	private RenewalLock $renewal_lock;

	private LoggerInterface $logger;

	private BillingPeriodCalculator $billing_calculator;

	public function __construct(
		SubscriptionRepository $repository,
		RenewalLock $renewal_lock,
		BillingPeriodCalculator $billing_calculator,
		LoggerInterface $logger
	) {
		$this->repository         = $repository;
		$this->renewal_lock       = $renewal_lock;
		$this->logger             = $logger;
		$this->billing_calculator = $billing_calculator;
	}

	public function execute( Renewal $renewal, ?string $old_status = null ): void {
		if ( $renewal->needs_payment() || $renewal->is_paid() === false ) {
			return;
		}

		$subscription = $this->repository->find( $renewal->get_subscription_id() );
		if ( ! $subscription instanceof Subscription ) {
			return;
		}

		if ( $old_status === 'failed' ) {
			do_action( 'fsub/subscription/payment_request/updated_failing_payment_method', $subscription, $renewal );
		}

		if ( $subscription->get_recent_payment_request_id() !== $renewal->get_id() ) {
			$this->logger->warning(
				'Order "{oid}" is not the latest payment request for subscription "{sid}". Skipping paid renewal.',
				[
					'sid'         => $subscription->get_id(),
					'oid'         => $renewal->get_id(),
					'skip_reason' => LifecycleSkipReason::RENEWAL_NOT_LATEST_PAYMENT_REQUEST,
				]
			);
			return;
		}

		if ( $renewal->is_period_advanced() ) {
			$this->logger->debug(
				'Renewal "{oid}" period already advanced for subscription "{sid}". Skipping.',
				[
					'sid'         => $subscription->get_id(),
					'oid'         => $renewal->get_id(),
					'skip_reason' => LifecycleSkipReason::RENEWAL_PERIOD_ALREADY_ADVANCED,
				]
			);
			return;
		}

		$lock_owner = $this->renewal_lock->acquire( $subscription->get_id(), MINUTE_IN_SECONDS );
		if ( $lock_owner === null ) {
			$this->logger->warning(
				'Failed to acquire renewal lock for subscription "{sid}" while processing paid renewal "{oid}".',
				[
					'sid'         => $subscription->get_id(),
					'oid'         => $renewal->get_id(),
					'skip_reason' => LifecycleSkipReason::RENEWAL_LOCK_NOT_ACQUIRED,
				]
			);
			return;
		}

		try {
			$next_period = $this->billing_calculator->calculate_next_period( $subscription->get_current_period_end(), $subscription->get_billing_frequency() );
			$advanced    = $subscription->advance_billing_period( $next_period );
			if ( ! $advanced ) {
				$this->logger->warning(
					'Failed to advance billing period for subscription "{sid}" and order "{oid}".',
					[
						'sid'         => $subscription->get_id(),
						'oid'         => $renewal->get_id(),
						'skip_reason' => LifecycleSkipReason::RENEWAL_PERIOD_ADVANCE_FAILED,
					]
				);
				return;
			}

			$renewal->mark_period_advanced();
			$renewal->save();

			$this->repository->save( $subscription );
		} catch ( \Throwable $e ) {
			$this->logger->warning(
				'Failed to process paid renewal for subscription "{sid}" and order "{oid}": {message}',
				[
					'sid'     => $subscription->get_id(),
					'oid'     => $renewal->get_id(),
					'message' => $e->getMessage(),
				]
			);
		} finally {
			$this->renewal_lock->release( $subscription->get_id(), $lock_owner );
		}
	}
}
