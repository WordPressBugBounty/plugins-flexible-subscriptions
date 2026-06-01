<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class Schedule {

	private const PAYMENT_REQUEST_ACTION = 'fsub/subscription/payment_request/process';

	private const PAYMENT_REQUEST_GROUP = 'fsb-payment-request';

	private const CANCELLATION_ACTION = 'fsub/subscription/cancel';

	private const CANCELLATION_GROUP = 'fsb-subscription-cancel';

	private const EXPIRATION_ACTION = 'fsub/subscription/expire';

	private const EXPIRATION_GROUP = 'fsb-subscription-expire';

	private LoggerInterface $logger;

	private ClockInterface $clock;

	public function __construct( ClockInterface $clock, LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->clock  = $clock;
	}

	public function schedule_payment_request( Subscription $subscription ): void {
		if ( $this->is_scheduled_payment_request( $subscription ) ) {
			$this->logger->debug(
				'billing.schedule.create.replacing',
				[
					'subscription_id' => $subscription->get_id(),
				]
			);
			as_unschedule_action(
				self::PAYMENT_REQUEST_ACTION,
				[
					'subscription' => $subscription->get_id(),
				],
				self::PAYMENT_REQUEST_GROUP
			);
		}

		if ( ! $subscription->is_active() ) {
			$this->logger->debug(
				'billing.schedule.create.skipped',
				[
					'subscription_id' => $subscription->get_id(),
					'current_status'  => $subscription->get_status(),
					'skip_reason'     => 'subscription_not_active',
				]
			);
			return;
		}

		if ( $subscription->is_expired() ) {
			$this->logger->debug(
				'billing.schedule.create.skipped',
				[
					'subscription_id' => $subscription->get_id(),
					'current_status'  => $subscription->get_status(),
					'skip_reason'     => 'subscription_expired',
				]
			);
			return;
		}

		$period = $subscription->get_current_period();

		if ( ! $period->getEndDate() instanceof \DateTimeInterface ) {
			$this->logger->warning(
				'billing.schedule.create.skipped',
				[
					'subscription_id' => $subscription->get_id(),
					'current_status'  => $subscription->get_status(),
					'skip_reason'     => 'missing_current_period_end',
				]
			);
			return;
		}

		if ( $period->getEndDate() <= $this->clock->now() ) {
			$this->logger->warning(
				'billing.schedule.create.skipped',
				[
					'subscription_id' => $subscription->get_id(),
					'scheduled_for'   => $period->getEndDate()->format( 'c' ),
					'current_status'  => $subscription->get_status(),
					'skip_reason'     => 'next_payment_in_past',
				]
			);
			return;
		}

		// Safeguard against zero-interval scheduling.
		if ( $subscription->get_billing_frequency()->isEmpty() ) {
			$this->logger->warning(
				'billing.schedule.create.skipped',
				[
					'subscription_id' => $subscription->get_id(),
					'current_status'  => $subscription->get_status(),
					'skip_reason'     => 'empty_billing_frequency',
				]
			);
			return;
		}

		$scheduled = as_schedule_single_action(
			$period->getEndDate()->getTimestamp(),
			self::PAYMENT_REQUEST_ACTION,
			[ 'subscription' => $subscription->get_id() ],
			self::PAYMENT_REQUEST_GROUP
		);

		if ( $scheduled !== 0 ) {
			$this->logger->debug(
				'billing.schedule.create.completed',
				[
					'subscription_id' => $subscription->get_id(),
					'scheduled_for'   => $period->getEndDate()->format( 'c' ),
				]
			);
		} else {
			$this->logger->warning(
				'billing.schedule.create.failed',
				[
					'subscription_id' => $subscription->get_id(),
					'scheduled_for'   => $period->getEndDate()->format( 'c' ),
				]
			);
		}
	}

	public function is_scheduled_payment_request( Subscription $subscription ): bool {
		return as_has_scheduled_action(
			self::PAYMENT_REQUEST_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::PAYMENT_REQUEST_GROUP
		);
	}

	public function remove_payment_request( Subscription $subscription ): void {
		as_unschedule_all_actions(
			self::PAYMENT_REQUEST_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::PAYMENT_REQUEST_GROUP
		);
	}

	/**
	 * Schedule subscription for cancellation with the end of the
	 * period.
	 */
	public function cancel( Subscription $subscription ): void {
		if ( ! $subscription->is_pending_cancel() || ! $subscription->get_end_date() instanceof \DateTimeInterface ) {
			return; // Nothing to schedule if no end date.
		}

		if ( $this->is_scheduled_cancel( $subscription ) ) {
			$this->logger->debug( sprintf( 'Subscription #%d is already scheduled for cancellation, unscheduling...', $subscription->get_id() ) );
			as_unschedule_action(
				self::CANCELLATION_ACTION,
				[
					'subscription' => $subscription->get_id(),
				],
				self::CANCELLATION_GROUP
			);
		}

		as_schedule_single_action(
			$subscription->get_end_date()->getTimestamp(),
			self::CANCELLATION_ACTION,
			[ 'subscription' => $subscription->get_id() ],
			self::CANCELLATION_GROUP
		);
	}

	public function remove_cancellation( Subscription $subscription ): bool {
		if ( ! $this->is_scheduled_cancel( $subscription ) ) {
			return false;
		}

		$this->logger->debug( 'Removing cancel schedule for subscription #{sid}...', [ 'sid' => $subscription->get_id() ] );
		return (bool) as_unschedule_action(
			self::CANCELLATION_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::CANCELLATION_GROUP
		);
	}

	public function is_scheduled_cancel( Subscription $subscription ): bool {
		return as_has_scheduled_action(
			self::CANCELLATION_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::CANCELLATION_GROUP
		);
	}

	public function schedule_expiration( Subscription $subscription ): void {
		if ( ! $subscription->can_be_updated_to( 'expired' ) || ! $subscription->get_end_date() instanceof \DateTimeInterface ) {
			return;
		}

		if ( $this->is_scheduled_expiration( $subscription ) ) {
			$this->logger->debug(
				'billing.schedule.expiration.replacing',
				[
					'subscription_id' => $subscription->get_id(),
				]
			);
			as_unschedule_action(
				self::EXPIRATION_ACTION,
				[
					'subscription' => $subscription->get_id(),
				],
				self::EXPIRATION_GROUP
			);
		}

		$scheduled = as_schedule_single_action(
			$subscription->get_end_date()->getTimestamp(),
			self::EXPIRATION_ACTION,
			[ 'subscription' => $subscription->get_id() ],
			self::EXPIRATION_GROUP
		);

		if ( $scheduled !== 0 ) {
			$this->logger->debug(
				'billing.schedule.expiration.completed',
				[
					'subscription_id' => $subscription->get_id(),
					'scheduled_for'   => $subscription->get_end_date()->format( 'c' ),
				]
			);
		} else {
			$this->logger->warning(
				'billing.schedule.expiration.failed',
				[
					'subscription_id' => $subscription->get_id(),
					'scheduled_for'   => $subscription->get_end_date()->format( 'c' ),
				]
			);
		}
	}

	public function remove_expiration( Subscription $subscription ): bool {
		if ( ! $this->is_scheduled_expiration( $subscription ) ) {
			return false;
		}

		$this->logger->debug( 'Removing expiration schedule for subscription #{sid}...', [ 'sid' => $subscription->get_id() ] );
		return (bool) as_unschedule_action(
			self::EXPIRATION_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::EXPIRATION_GROUP
		);
	}

	public function is_scheduled_expiration( Subscription $subscription ): bool {
		return as_has_scheduled_action(
			self::EXPIRATION_ACTION,
			[
				'subscription' => $subscription->get_id(),
			],
			self::EXPIRATION_GROUP
		);
	}
}
