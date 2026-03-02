<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Lifecycle\LifecycleOrchestrator;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use const DAY_IN_SECONDS;

/**
 * Sometimes, subscription may stuck in on-hold status, even despite the fact that the payment
 * request was successfully made. This hook tries to recover the subscription periodically, checking
 * for last payment request and verifying that it was successful.
 */
final class AutorecoverOnHoldSubscriptions implements HookProvider {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	public function __construct( SubscriptionRepository $repository, ClockInterface $clock ) {
		$this->repository = $repository;
		$this->clock      = $clock;
	}

	public function hooks(): void {
		if ( did_action( 'action_scheduler_init' ) ) {
			$this->hook_schedule();
		} else {
			add_action( 'action_scheduler_init', [ $this, 'hook_schedule' ] );
		}

		add_action( 'fsub/subscription/autorecover_on_hold_subscriptions', $this );
	}

	public function hook_schedule(): void {
		if ( false === as_next_scheduled_action( 'fsub/subscription/autorecover_on_hold_subscriptions' ) ) {
			as_schedule_recurring_action( (int) strtotime( 'midnight tonight' ), DAY_IN_SECONDS, 'fsub/subscription/autorecover_on_hold_subscriptions' );
		}
	}

	public function __invoke(): void {
		$subscriptions = $this->repository->find_all_by(
			[
				'status'     => 'on-hold',
				'meta_query' => [
					[
						'key'     => '_recent_payment_request_id',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		foreach ( $subscriptions as $subscription ) {
			$last_payment_request_id = $subscription->get_recent_payment_request_id();

			if ( empty( $last_payment_request_id ) ) {
				continue;
			}

			$payment_request = wc_get_order( $last_payment_request_id );

			if ( ! $payment_request instanceof WC_Order ) {
				continue;
			}

			if ( $payment_request->is_paid() ) {
				$subscription->reactivate( TransitionContext::system( 'autorecover_on_hold' ) );
				$subscription->add_order_note( __( 'Subscription reactivated because the last payment request was successful.', 'flexible-subscriptions' ) );
				$this->repository->save( $subscription );
			}
		}
	}
}
