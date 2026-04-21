<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class Schedule {

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
				'fsub/subscription/payment_request/process',
				[
					'subscription' => $subscription->get_id(),
				],
				'fsb-payment-request'
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
			'fsub/subscription/payment_request/process',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-payment-request'
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
			'fsub/subscription/payment_request/process',
			[
				'subscription' => $subscription->get_id(),
			],
			'fsb-payment-request'
		);
	}

	public function remove_payment_request( Subscription $subscription ): void {
		as_unschedule_all_actions(
			'fsub/subscription/payment_request/process',
			[
				'subscription' => $subscription->get_id(),
			],
			'fsb-payment-request'
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
				'fsub/subscription/cancel',
				[
					'subscription' => $subscription->get_id(),
				],
				'fsb-subscription-cancel'
			);
		}

		as_schedule_single_action(
			$subscription->get_end_date()->getTimestamp(),
			'fsub/subscription/cancel',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-subscription-cancel'
		);
	}

	public function remove_cancellation( Subscription $subscription ): bool {
		if ( ! $this->is_scheduled_cancel( $subscription ) ) {
			return false;
		}

		$this->logger->debug( 'Removing cancel schedule for subscription #{sid}...', [ 'sid' => $subscription->get_id() ] );
		return (bool) as_unschedule_action(
			'fsub/subscription/cancel',
			[
				'subscription' => $subscription->get_id(),
			],
			'fsb-subscription-cancel'
		);
	}

	public function is_scheduled_cancel( Subscription $subscription ): bool {
		return as_has_scheduled_action(
			'fsub/subscription/cancel',
			[
				'subscription' => $subscription->get_id(),
			],
			'fsb-subscription-cancel'
		);
	}
}
