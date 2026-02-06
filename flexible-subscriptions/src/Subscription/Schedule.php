<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class Schedule {

	private LoggerInterface $logger;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function schedule_payment_request( Subscription $subscription ): ?SchedulePaymentRequestDecision {
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

		if ( $subscription->is_expired() ) {
			$this->logger->debug( sprintf( 'Subscription #%d is already expired, not scheduling payment request.', $subscription->get_id() ) );
			return new SchedulePaymentRequestDecision( SchedulePaymentRequestDecision::TRANSITION_EXPIRED );
		}

		$period = $subscription->get_current_period();

		if ( ! $period->getEndDate() instanceof \DateTimeInterface ) {
			$this->logger->warning( sprintf( 'Subscription period is missing end date for subscription #%d. Cannot schedule payment request.', $subscription->get_id() ) );
			return null;
		}

		$now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		if ( $period->getEndDate() <= $now ) {
			$this->logger->warning(
				'Aborting payment schedule for subscription #{id} because next payment date is in the past: "{stamp}".',
				[
					'id'           => $subscription->get_id(),
					'subscription' => (string) $subscription,
					'stamp'        => $period->getEndDate()->format( 'c' ),
					'period'       => $period,
				]
			);
			return null;
		}

		// Safeguard against zero-interval scheduling.
		if ( $subscription->get_billing_frequency()->isEmpty() ) {
			$this->logger->critical(
				'Aborting payment schedule for subscription #{id} due to a zero-length billing interval. The subscription has been put on hold to prevent repeated charges.',
				[
					'id'           => $subscription->get_id(),
					'subscription' => (string) $subscription,
				]
			);
			return new SchedulePaymentRequestDecision(
				SchedulePaymentRequestDecision::TRANSITION_ON_HOLD,
				__( 'Subscription put on hold by the system due to a configuration error (zero-length billing interval). Please check the subscription settings.', 'flexible-subscriptions' )
			);
		}

		$this->logger->debug(
			'Scheduling payment request for subscription #{id} at "{stamp}"...',
			[
				'id'           => $subscription->get_id(),
				'subscription' => (string) $subscription,
				'stamp'        => $period->getEndDate()->format( 'c' ),
				'period'       => $period,
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
					'id'     => $subscription->get_id(),
					'period' => $period,
				]
			);
		} else {
			$this->logger->warning(
				'Failed to schedule payment request for subscription #{id}',
				[
					'id'     => $subscription->get_id(),
					'period' => $period,
				]
			);
		}

		return null;
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
