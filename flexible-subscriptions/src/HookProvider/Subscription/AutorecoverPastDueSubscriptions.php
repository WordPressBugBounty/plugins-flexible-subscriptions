<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Schedule;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Attempt to recover subscriptions with a next payment date set in the past.
 *
 * This can happen if the renewal payment succeeds without advancing the billing period,
 * which may lead to scheduling an immediate extra renewal order.
 */
final class AutorecoverPastDueSubscriptions implements HookProvider {

	private const ACTION = 'fsub/subscription/autorecover_past_due_subscriptions';

	private SubscriptionFinder $finder;

	private Schedule $schedule;

	private RenewalLock $renewal_lock;

	private SubscriptionLifecycleManager $lifecycle;

	private LoggerInterface $logger;

	public function __construct( SubscriptionFinder $finder, Schedule $schedule, RenewalLock $renewal_lock, SubscriptionLifecycleManager $lifecycle, LoggerInterface $logger ) {
		$this->finder       = $finder;
		$this->schedule     = $schedule;
		$this->renewal_lock = $renewal_lock;
		$this->lifecycle    = $lifecycle;
		$this->logger       = $logger;
	}

	public function hooks(): void {
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( self::ACTION ) ) {
			as_schedule_recurring_action( (int) strtotime( 'midnight tonight' ), \DAY_IN_SECONDS, self::ACTION );
		}

		add_action( self::ACTION, $this );
	}

	public function __invoke(): void {
		$now_utc = gmdate( 'Y-m-d H:i:s' );

		$subscriptions = $this->finder->find_all_by(
			[
				'status'     => 'active',
				'meta_query' => [
					[
						'key'     => '_recent_payment_request_id',
						'compare' => 'EXISTS',
					],
					[
						'key'     => '_current_period_end_utc',
						'value'   => $now_utc,
						'compare' => '<',
						'type'    => 'DATETIME',
					],
				],
			]
		);

		$now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_expired() ) {
				continue;
			}

			$last_payment_request_id = $subscription->get_recent_payment_request_id();
			if ( empty( $last_payment_request_id ) ) {
				continue;
			}

			$payment_request = wc_get_order( $last_payment_request_id );
			if ( ! $payment_request instanceof \WC_Order || ! $payment_request->is_paid() ) {
				continue;
			}

			$lock_owner = $this->renewal_lock->acquire( $subscription->get_id(), 600 );
			if ( $lock_owner === null ) {
				continue;
			}

			try {
				$current_end = $subscription->get_current_period_end();
				if ( ! $current_end instanceof \DateTimeInterface || $current_end > $now ) {
					continue;
				}

				if ( ! $subscription->advance_billing_period() ) {
					continue;
				}

				$subscription->set_billing_cycle( $subscription->get_billing_cycle() + 1 );

				// Mark the last paid renewal as already processed to reduce chances of double-advancement.
				if ( ! (bool) $payment_request->get_meta( '_fsb_renewal_period_advanced', true ) ) {
					$payment_request->update_meta_data( '_fsb_renewal_period_advanced', '1' );
					$payment_request->save();
				}

				$subscription->save();

				$this->lifecycle->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'autorecover_reschedule' ) );
				$this->logger->info(
					'Autorecovered past-due subscription "{sid}" by advancing billing period by one cycle.',
					[
						'sid' => $subscription->get_id(),
					]
				);
			} catch ( \Throwable $e ) {
				$this->logger->warning(
					'Failed to reschedule payment request while autorecovering subscription "{sid}": {message}',
					[
						'sid'     => $subscription->get_id(),
						'message' => $e->getMessage(),
					]
				);
			} finally {
				$this->renewal_lock->release( $subscription->get_id(), $lock_owner );
			}
		}
	}
}
