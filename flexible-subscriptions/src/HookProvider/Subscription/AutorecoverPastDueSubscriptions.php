<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Actions\ProcessPaidRenewal;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Schedule;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Attempt to recover subscriptions with a next payment date set in the past.
 *
 * This can happen if the renewal payment succeeds without advancing the billing period,
 * which may lead to scheduling an immediate extra renewal order.
 */
final class AutorecoverPastDueSubscriptions implements HookProvider {

	private const ACTION              = 'fsub/subscription/autorecover_past_due_subscriptions';
	private const INTERVAL_IN_SECONDS = 15 * MINUTE_IN_SECONDS;
	private const BATCH_SIZE          = 100;

	private SubscriptionFinder $finder;

	private ProcessPaidRenewal $process_paid_renewal;

	private Schedule $schedule;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	public function __construct(
		SubscriptionFinder $finder,
		ProcessPaidRenewal $process_paid_renewal,
		Schedule $schedule,
		ClockInterface $clock,
		LoggerInterface $logger
	) {
		$this->finder               = $finder;
		$this->process_paid_renewal = $process_paid_renewal;
		$this->schedule             = $schedule;
		$this->clock                = $clock;
		$this->logger               = $logger;
	}

	public function hooks(): void {
		if ( did_action( 'action_scheduler_init' ) ) {
			$this->hook_schedule();
		} else {
			add_action( 'action_scheduler_init', [ $this, 'hook_schedule' ] );
		}

		add_action( self::ACTION, $this );
	}

	public function hook_schedule(): void {
		if ( $this->has_expected_schedule() ) {
			return;
		}

		as_unschedule_all_actions( self::ACTION );
		as_schedule_recurring_action( time() + self::INTERVAL_IN_SECONDS, self::INTERVAL_IN_SECONDS, self::ACTION, [], '', true );
	}

	public function __invoke(): void {
		$subscriptions = $this->finder->find_all_by(
			[
				'status'     => 'active',
				'limit'      => self::BATCH_SIZE,
				'meta_query' => [
					[
						'key'     => '_current_period_end_utc',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		$now = $this->clock->now();
		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription instanceof Subscription ) {
				continue;
			}

			if ( $subscription->is_manual() || $subscription->is_expired( $now ) ) {
				continue;
			}

			$current_period_end = $subscription->get_current_period_end();
			if ( ! $current_period_end instanceof \DateTimeInterface ) {
				continue;
			}

			if ( $current_period_end > $now ) {
				$this->restore_missing_schedule( $subscription, $current_period_end );
				continue;
			}

			$this->recover_stale_paid_renewal( $subscription, $current_period_end );
		}
	}

	private function recover_stale_paid_renewal( Subscription $subscription, \DateTimeInterface $current_period_end ): void {
		$payment_request_id = $subscription->get_recent_payment_request_id();
		if ( $payment_request_id === null ) {
			$this->log_anomaly( $subscription, $current_period_end, null, 'missing_recent_payment_request_id' );
			return;
		}

		$payment_request = wc_get_order( $payment_request_id );
		if ( ! $payment_request instanceof \WC_Order ) {
			$this->log_anomaly( $subscription, $current_period_end, $payment_request_id, 'payment_request_not_found' );
			return;
		}

		$renewal = Renewal::from_order( $payment_request );
		if ( ! $renewal instanceof Renewal ) {
			$this->log_anomaly( $subscription, $current_period_end, $payment_request_id, 'payment_request_not_renewal' );
			return;
		}

		if ( ! $renewal->is_paid() ) {
			$this->log_anomaly( $subscription, $current_period_end, $payment_request_id, 'renewal_not_paid' );
			return;
		}

		$was_period_advanced = $renewal->is_period_advanced();
		$this->process_paid_renewal->execute( $renewal );

		if ( ! $was_period_advanced && $renewal->is_period_advanced() ) {
			$this->logger->info(
				'billing.reconcile.paid_renewal_recovered',
				[
					'subscription_id'    => $subscription->get_id(),
					'renewal_id'         => $renewal->get_id(),
					'payment_request_id' => $payment_request_id,
					'current_period_end' => $current_period_end->format( 'c' ),
					'reconciler_action'  => 'process_paid_renewal',
				]
			);
		}
	}

	private function restore_missing_schedule( Subscription $subscription, \DateTimeInterface $current_period_end ): void {
		if ( $this->schedule->is_scheduled_payment_request( $subscription ) ) {
			return;
		}

		$this->schedule->schedule_payment_request( $subscription );
		if ( $this->schedule->is_scheduled_payment_request( $subscription ) ) {
			$this->logger->info(
				'billing.reconcile.schedule_restored',
				[
					'subscription_id'    => $subscription->get_id(),
					'current_period_end' => $current_period_end->format( 'c' ),
					'scheduled_for'      => $current_period_end->format( 'c' ),
					'reconciler_action'  => 'schedule_payment_request',
				]
			);
		}
	}

	private function has_expected_schedule(): bool {
		$actions = as_get_scheduled_actions(
			[
				'hook'     => self::ACTION,
				'status'   => 'pending',
				'per_page' => 1,
				'orderby'  => 'date',
				'order'    => 'ASC',
			],
			OBJECT
		);

		if ( ! is_array( $actions ) || $actions === [] ) {
			return false;
		}

		$action = reset( $actions );
		if ( ! is_object( $action ) || ! method_exists( $action, 'get_schedule' ) ) {
			return false;
		}

		$schedule = $action->get_schedule();
		if ( ! is_object( $schedule ) || ! method_exists( $schedule, 'is_recurring' ) || ! $schedule->is_recurring() ) {
			return false;
		}

		return method_exists( $schedule, 'get_recurrence' ) && (int) $schedule->get_recurrence() === self::INTERVAL_IN_SECONDS;
	}

	private function log_anomaly( Subscription $subscription, \DateTimeInterface $current_period_end, ?int $payment_request_id, string $skip_reason ): void {
		$this->logger->warning(
			'billing.reconcile.anomaly',
			[
				'subscription_id'    => $subscription->get_id(),
				'payment_request_id' => $payment_request_id,
				'current_period_end' => $current_period_end->format( 'c' ),
				'skip_reason'        => $skip_reason,
			]
		);
	}
}
