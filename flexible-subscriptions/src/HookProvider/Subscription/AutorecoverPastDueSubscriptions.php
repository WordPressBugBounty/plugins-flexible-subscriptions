<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Actions\ProcessPaidRenewal;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;

/**
 * Attempt to recover subscriptions with a next payment date set in the past.
 *
 * This can happen if the renewal payment succeeds without advancing the billing period,
 * which may lead to scheduling an immediate extra renewal order.
 */
final class AutorecoverPastDueSubscriptions implements HookProvider {

	private const ACTION = 'fsub/subscription/autorecover_past_due_subscriptions';

	private SubscriptionFinder $finder;

	private ProcessPaidRenewal $process_paid_renewal;

	private ClockInterface $clock;

	public function __construct( SubscriptionFinder $finder, ProcessPaidRenewal $process_paid_renewal, ClockInterface $clock ) {
		$this->finder               = $finder;
		$this->process_paid_renewal = $process_paid_renewal;
		$this->clock                = $clock;
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
		if ( false === as_next_scheduled_action( self::ACTION ) ) {
			as_schedule_recurring_action( (int) strtotime( 'midnight tonight' ), \DAY_IN_SECONDS, self::ACTION );
		}
	}

	public function __invoke(): void {
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
						'value'   => $this->clock->now()->format( 'Y-m-d H:i:s' ),
						'compare' => '<',
						'type'    => 'DATETIME',
					],
				],
			]
		);

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_expired( $this->clock->now() ) ) {
				continue;
			}

			$last_payment_request_id = $subscription->get_recent_payment_request_id();
			if ( empty( $last_payment_request_id ) ) {
				continue;
			}

			$payment_request = wc_get_order( $last_payment_request_id );
			$this->process_paid_renewal->execute( $payment_request );
		}
	}
}
