<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Centralized lifecycle/workflow manager for subscription transitions and renewals.
 *
 * Goals:
 * - Single place for transition side effects (scheduling, dates, notes).
 * - Idempotent paid-renewal processing (handled here).
 * - Avoid duplicated side effects when status changes are triggered via admin UI.
 */
final class SubscriptionLifecycleManager {

	private Schedule $schedule;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	private RenewalLock $renewal_lock;

	/**
	 * Allowed transitions map: from -> to -> list of side-effect callbacks.
	 *
	 * Side effects are executed AFTER the status change is persisted, so they can rely on
	 * the current subscription status (e.g. Schedule::cancel() checks pending-cancel).
	 *
	 * @var array<string, array<string, list<non-empty-string>>>
	 */
	private array $transitions = [
		'pending'        => [
			'active'         => [ 'on_activation' ],
			'on-hold'        => [ 'on_hold' ],
			'pending-cancel' => [ 'on_pending_cancellation' ],
			// Allow immediate cancellation of pending subscriptions.
			'cancelled'      => [ 'on_cancellation_fulfilled' ],
			'trash'          => [],
		],
		'on-hold'        => [
			'active'         => [ 'on_activation' ],
			'pending-cancel' => [ 'on_pending_cancellation' ],
			'expired'        => [ 'on_expired' ],
			'trash'          => [],
		],
		'active'         => [
			'on-hold'        => [ 'on_hold' ],
			'pending-cancel' => [ 'on_pending_cancellation' ],
			'expired'        => [ 'on_expired' ],
			'trash'          => [],
		],
		'pending-cancel' => [
			'active'    => [ 'on_activation' ],
			'cancelled' => [ 'on_cancellation_fulfilled' ],
			'trash'     => [],
		],
		// --- Terminal / restricted states ---
		'cancelled'      => [
			'trash' => [],
		],
		'expired'        => [
			'trash' => [],
		],
		'trash'          => [],
	];

	public function __construct(
		Schedule $schedule,
		ClockInterface $clock,
		RenewalLock $renewal_lock,
		LoggerInterface $logger
	) {
		$this->schedule     = $schedule;
		$this->clock        = $clock;
		$this->renewal_lock = $renewal_lock;
		$this->logger       = $logger;
	}

	public function can_transition( Subscription $subscription, string $to ): bool {
		$from = $subscription->get_status();
		$to   = $this->resolve_target_status( $subscription, $to );

		return isset( $this->transitions[ $from ][ $to ] );
	}

	public function transition( Subscription $subscription, string $to, ?TransitionContext $context = null ): bool {
		$context ??= TransitionContext::system();

		$from = $subscription->get_status();
		$to   = $this->resolve_target_status( $subscription, $to );

		if ( $from === $to ) {
			return true;
		}

		if ( ! isset( $this->transitions[ $from ][ $to ] ) ) {
			$this->logger->warning(
				'Attempted invalid subscription transition "{from}" -> "{to}" for "{sid}".',
				[
					'from' => $from,
					'to'   => $to,
					'sid'  => $subscription->get_id(),
				]
			);
			return false;
		}

		$callbacks = $this->transitions[ $from ][ $to ];

		$subscription->begin_lifecycle_transition();
		try {
			$updated = $subscription->update_status( $to, $context->note, $context->manual );
			if ( ! $updated ) {
				return false;
			}

			foreach ( $callbacks as $method_name ) {
				$this->{$method_name}( $subscription, $from, $context );
			}

			// Persist side-effect changes (dates/meta) without triggering status transition again.
			$subscription->save();

			return true;
		} finally {
			$subscription->end_lifecycle_transition();
		}
	}

	/**
	 * Handles side effects for transitions that happened outside the lifecycle manager
	 * (e.g. via admin UI / direct status edits).
	 *
	 * This method MUST NOT call update_status()/set_status() to avoid recursion.
	 */
	public function handle_external_status_change(
		Subscription $subscription,
		string $to,
		?string $from = null,
		?TransitionContext $context = null
	): void {
		$context ??= TransitionContext::manual( 'external_status_change' );

		$to = $this->normalize_status( $to );
		if ( $from !== null ) {
			$from = $this->normalize_status( $from );
		}

		// If we have explicit mapping for this transition, use it. Otherwise fall back to a "target status" handler,
		// because external changes (admin UI, integrations) can bypass allowed transition graph.
		$callbacks = $from !== null ? ( $this->transitions[ $from ][ $to ] ?? null ) : null;
		if ( is_array( $callbacks ) ) {
			foreach ( $callbacks as $method_name ) {
				$this->{$method_name}( $subscription, $from, $context );
			}
		} else {
			$this->apply_target_status_side_effects( $subscription, $from ?? '', $to, $context );
		}

		// Persist side effects (dates/meta) without re-triggering status transition.
		$subscription->save();
	}

	public function process_paid_renewal( Subscription $subscription, WC_Order $order, bool $was_active_before_payment ): void {
		if ( ! $subscription->get_current_period_end() instanceof \DateTimeInterface ) {
			return;
		}

		// Only process renewal orders.
		$renewal = Renewal::from_order( $order );
		if ( ! $renewal instanceof Renewal ) {
			// Fallback for legacy orders without proper meta - check subscription association.
			$renewal_id = (int) $order->get_meta( Renewal::META_SUBSCRIPTION_ID, true );
			if ( $renewal_id !== $subscription->get_id() ) {
				return;
			}
		}

		// Ensure this is the latest renewal we consider for the subscription.
		if ( $subscription->get_recent_payment_request_id() !== $order->get_id() ) {
			return;
		}

		// Fast idempotency: do not advance the billing period twice for the same order.
		if ( $renewal instanceof Renewal && $renewal->is_period_advanced() ) {
			return;
		}
		if ( ! $renewal instanceof Renewal && (bool) $order->get_meta( Renewal::META_PERIOD_ADVANCED, true ) ) {
			return;
		}

		$subscription_id = (int) $subscription->get_id();
		$lock_owner      = $this->renewal_lock->acquire( $subscription_id, \MINUTE_IN_SECONDS );
		if ( $lock_owner === null ) {
			$this->logger->warning(
				'Failed to acquire renewal lock for subscription "{sid}" while processing paid renewal "{oid}".',
				[
					'sid' => $subscription_id,
					'oid' => $order->get_id(),
				]
			);
			return;
		}

		try {
			// Re-check idempotency under the lock.
			if ( $renewal instanceof Renewal && $renewal->is_period_advanced() ) {
				return;
			}
			if ( ! $renewal instanceof Renewal && (bool) $order->get_meta( Renewal::META_PERIOD_ADVANCED, true ) ) {
				return;
			}

			$advanced = $subscription->advance_billing_period();
			if ( ! $advanced ) {
				return;
			}

			$subscription->set_billing_cycle( $subscription->get_billing_cycle() + 1 );

			if ( $renewal instanceof Renewal ) {
				$renewal->mark_period_advanced();
				$renewal->save();
			} else {
				$order->update_meta_data( Renewal::META_PERIOD_ADVANCED, '1' );
				$order->save();
			}

			$subscription->save();

			$this->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'schedule_after_paid_renewal' ) );
		} catch ( \Throwable $e ) {
			$this->logger->warning(
				'Failed to process paid renewal for subscription "{sid}" and order "{oid}": {message}',
				[
					'sid'     => $subscription_id,
					'oid'     => $order->get_id(),
					'message' => $e->getMessage(),
				]
			);
		} finally {
			$this->renewal_lock->release( $subscription_id, $lock_owner );
		}
	}

	private function apply_target_status_side_effects( Subscription $subscription, string $from, string $to, TransitionContext $context ): void {
		switch ( $to ) {
			case 'active':
				$this->on_activation( $subscription, $from, $context );
				return;
			case 'on-hold':
				$this->on_hold( $subscription, $from, $context );
				return;
			case 'pending-cancel':
				$this->on_pending_cancellation( $subscription, $from, $context );
				return;
			case 'cancelled':
				$this->on_cancellation_fulfilled( $subscription, $from, $context );
				return;
			default:
				return;
		}
	}

	private function on_activation( Subscription $subscription, string $from, TransitionContext $context ): void {
		$this->logger->debug(
			'LifecycleManager: activating subscription "{sid}" from "{from}" (reason "{reason}")',
			[
				'sid'    => $subscription->get_id(),
				'from'   => $from,
				'reason' => $context->reason,
			]
		);

		// If the subscription is activated after the initial payment (pending -> active),
		// ensure dates are initialized for the first billing period.
		if ( $from === 'pending' && ! $subscription->has_trial() ) {
			$subscription->set_current_period_start( $this->clock->now() );
			$subscription->recalculate_periods();
		}

		$from_cancellation = in_array( $from, [ 'pending-cancel', 'cancelled' ], true )
			|| $subscription->get_cancelled_date() instanceof \DateTimeInterface;
		if ( $from_cancellation ) {
			$subscription->set_cancelled_date( null );
			$subscription->recalculate_periods();
		}

		$this->schedule->remove_cancellation( $subscription );
		$this->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'schedule_after_activation' ) );
	}

	private function on_pending_cancellation( Subscription $subscription, string $from, TransitionContext $context ): void {
		$this->logger->debug(
			'LifecycleManager: pending cancellation for "{sid}" from "{from}" (reason "{reason}")',
			[
				'sid'    => $subscription->get_id(),
				'from'   => $from,
				'reason' => $context->reason,
			]
		);

		$now = $this->clock->now();

		if ( $subscription->get_current_period_end() instanceof \DateTimeInterface ) {
			$subscription->set_end_date( $subscription->get_current_period_end() );
		}

		$subscription->set_cancelled_date( $now );

		$this->schedule->cancel( $subscription );
	}

	private function on_cancellation_fulfilled( Subscription $subscription, string $from, TransitionContext $context ): void {
		$this->logger->debug(
			'LifecycleManager: cancelling subscription "{sid}" from "{from}" (reason "{reason}")',
			[
				'sid'    => $subscription->get_id(),
				'from'   => $from,
				'reason' => $context->reason,
			]
		);

		$now = $this->clock->now();

		if ( ! $subscription->get_cancelled_date() instanceof \DateTimeInterface ) {
			$subscription->set_cancelled_date( $now );
		}

		$subscription->set_end_date( $now );
		$subscription->set_current_period_end( null );

		$this->schedule->remove_cancellation( $subscription );
		// Avoid stale payment requests after cancellation.
		as_unschedule_all_actions(
			'fsub/subscription/payment_request/process',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-payment-request'
		);
	}

	private function on_expired( Subscription $subscription, string $from, TransitionContext $context ): void {
		$this->logger->debug(
			'LifecycleManager: expiring subscription "{sid}" from "{from}" (reason "{reason}")',
			[
				'sid'    => $subscription->get_id(),
				'from'   => $from,
				'reason' => $context->reason,
			]
		);

		if ( ! $subscription->get_end_date() instanceof \DateTimeInterface ) {
			$subscription->set_end_date( $this->clock->now() );
		}

		$this->schedule->remove_cancellation( $subscription );
		as_unschedule_all_actions(
			'fsub/subscription/payment_request/process',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-payment-request'
		);
	}

	private function on_hold( Subscription $subscription, string $from, TransitionContext $context ): void {
		$this->logger->debug(
			'LifecycleManager: putting subscription "{sid}" on hold from "{from}" (reason "{reason}")',
			[
				'sid'    => $subscription->get_id(),
				'from'   => $from,
				'reason' => $context->reason,
			]
		);

		as_unschedule_all_actions(
			'fsub/subscription/payment_request/process',
			[ 'subscription' => $subscription->get_id() ],
			'fsb-payment-request'
		);
	}

	private function normalize_status( string $status ): string {
		if ( str_starts_with( $status, 'wc-' ) ) {
			return substr( $status, 3 );
		}
		return $status;
	}

	private function resolve_target_status( Subscription $subscription, string $to ): string {
		$to   = $this->normalize_status( $to );
		$from = $subscription->get_status();

		// Decide final status before persisting.
		if ( $to === 'pending-cancel' && $from === 'pending' && $subscription->is_during_first_cycle() ) {
			return 'cancelled';
		}

		return $to;
	}

	public function schedule_next_payment_request_or_transition( Subscription $subscription, TransitionContext $context ): void {
		$decision = $this->schedule->schedule_payment_request( $subscription );
		if ( ! $decision instanceof SchedulePaymentRequestDecision ) {
			return;
		}

		$this->transition(
			$subscription,
			$decision->transition_to,
			TransitionContext::system( $context->reason, $decision->note )
		);
	}
}
