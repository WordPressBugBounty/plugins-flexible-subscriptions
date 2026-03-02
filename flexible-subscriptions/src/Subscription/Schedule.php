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
			$this->logger->debug( sprintf( 'Subscription #%d is already scheduled for payment request, unscheduling...', $subscription->get_id() ) );
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
				'Aborting payment schedule for subscription #{id} because subscription is not active.',
				[
					'id'             => $subscription->get_id(),
					'current_status' => $subscription->get_status(),
				]
			);
			return;
		}

		if ( $subscription->is_expired() ) {
			$this->logger->debug( sprintf( 'Subscription #%d is already expired, not scheduling payment request.', $subscription->get_id() ) );
			return;
		}

		$period = $subscription->get_current_period();

		if ( ! $period->getEndDate() instanceof \DateTimeInterface ) {
			$this->logger->warning( sprintf( 'Subscription period is missing end date for subscription #%d. Cannot schedule payment request.', $subscription->get_id() ) );
			return;
		}

		if ( $period->getEndDate() <= $this->clock->now() ) {
			$this->logger->warning(
				'Aborting payment schedule for subscription #{id} because next payment date is in the past: "{stamp}".',
				[
					'id'             => $subscription->get_id(),
					'stamp'          => $period->getEndDate()->format( 'c' ),
					'current_status' => $subscription->get_status(),
				]
			);
			return;
		}

		// Safeguard against zero-interval scheduling.
		if ( $subscription->get_billing_frequency()->isEmpty() ) {
			$this->logger->warning(
				'Aborting payment schedule for subscription #{id} due to a zero-length billing interval.',
				[
					'id'             => $subscription->get_id(),
					'current_status' => $subscription->get_status(),
				]
			);
			return;
		}

		$this->logger->debug(
			'Scheduling payment request for subscription #{id} at "{stamp}"...',
			[
				'id'             => $subscription->get_id(),
				'stamp'          => $period->getEndDate()->format( 'c' ),
				'current_status' => $subscription->get_status(),
			]
		);

		$scheduled = as_schedule_single_action(
			$period->getEndDate()->getTimestamp(),
			'fsub/subscription/payment_request/process',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-payment-request'
		);

		if ( $scheduled !== 0 ) {
			$this->logger->debug(
				'Succesfully scheduled payment request for subscription #{id}',
				[
					'id'    => $subscription->get_id(),
					'stamp' => $period->getEndDate()->format( 'c' ),
				]
			);
		} else {
			$this->logger->warning(
				'Failed to schedule payment request for subscription #{id}',
				[
					'id'    => $subscription->get_id(),
					'stamp' => $period->getEndDate()->format( 'c' ),
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
