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
		$original_renewal = $renewal;

		if ( $renewal->needs_payment() || $renewal->is_paid() === false ) {
			return;
		}

		$subscription = $this->repository->find( $renewal->get_subscription_id() );
		if ( ! $subscription instanceof Subscription ) {
			return;
		}

		if ( $subscription->is_finalized() ) {
			$this->logger->warning(
				'billing.renewal.process_paid.skipped',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'skip_reason'        => LifecycleSkipReason::SUBSCRIPTION_FINALIZED,
				]
			);
			return;
		}

		if ( $old_status === 'failed' ) {
			do_action( 'fsub/subscription/payment_request/updated_failing_payment_method', $subscription, $renewal );
		}

		if ( $subscription->get_recent_payment_request_id() !== $renewal->get_id() ) {
			$this->logger->warning(
				'billing.renewal.process_paid.skipped',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'skip_reason'        => LifecycleSkipReason::RENEWAL_NOT_LATEST_PAYMENT_REQUEST,
				]
			);
			return;
		}

		if ( $renewal->is_period_advanced() ) {
			$this->logger->debug(
				'billing.renewal.process_paid.skipped',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'skip_reason'        => LifecycleSkipReason::RENEWAL_PERIOD_ALREADY_ADVANCED,
				]
			);
			return;
		}

		$lock_owner = $this->renewal_lock->acquire( $subscription->get_id(), MINUTE_IN_SECONDS );
		if ( $lock_owner === null ) {
			$this->logger->warning(
				'billing.renewal.process_paid.skipped',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'skip_reason'        => LifecycleSkipReason::RENEWAL_LOCK_NOT_ACQUIRED,
				]
			);
			return;
		}

		try {
			$renewal_and_subscription = $this->refresh_renewal_and_subscription( $renewal );
			if ( $renewal_and_subscription === null ) {
				return;
			}
			[ $renewal, $subscription ] = $renewal_and_subscription;

			$next_period = $this->billing_calculator->calculate_next_period( $subscription->get_current_period_end(), $subscription->get_billing_frequency() );
			$advanced    = $subscription->advance_billing_period( $next_period );
			if ( ! $advanced ) {
				$this->logger->warning(
					'billing.renewal.process_paid.skipped',
					[
						'subscription_id'    => $subscription->get_id(),
						'renewal_id'         => $renewal->get_id(),
						'payment_request_id' => $renewal->get_id(),
						'skip_reason'        => LifecycleSkipReason::RENEWAL_PERIOD_ADVANCE_FAILED,
					]
				);
				return;
			}

			$renewal->mark_period_advanced();
			$renewal->save();
			$original_renewal->mark_period_advanced();

			$this->repository->save( $subscription );
			$current_period_end = $subscription->get_current_period_end();
			$this->logger->info(
				'billing.renewal.process_paid.completed',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'billing_cycle'      => $subscription->get_billing_cycle(),
					'current_period_end' => $current_period_end instanceof \DateTimeInterface ? $current_period_end->format( 'c' ) : null,
					'order_status_from'  => $old_status,
				]
			);
		} catch ( \Throwable $e ) {
			$this->logger->warning(
				'billing.renewal.process_paid.failed',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $renewal->get_id(),
					'message'            => $e->getMessage(),
				]
			);
		} finally {
			$this->renewal_lock->release( $subscription->get_id(), $lock_owner );
		}
	}

	/** @return array{Renewal, Subscription}|null */
	private function refresh_renewal_and_subscription( Renewal $renewal ): ?array {
		$fresh_order = wc_get_order( $renewal->get_id() );
		if ( ! $fresh_order instanceof \WC_Order ) {
			return null;
		}

		$renewal = Renewal::from_order( $fresh_order );
		if ( ! $renewal instanceof Renewal || $renewal->is_period_advanced() ) {
			return null;
		}

		$subscription = $this->repository->find( $renewal->get_subscription_id() );
		if ( ! $subscription instanceof Subscription || $subscription->get_recent_payment_request_id() !== $renewal->get_id() ) {
			return null;
		}

		return [ $renewal, $subscription ];
	}
}
