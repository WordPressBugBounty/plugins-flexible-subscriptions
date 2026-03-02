<?php

namespace WPDesk\FlexibleSubscriptions\Subscription\Actions;

use WPDesk\FlexibleSubscriptions\Subscription\BillingPeriodCalculator;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

final class ActivateSubscription {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	private BillingPeriodCalculator $calculator;

	public function __construct(
		SubscriptionRepository $repository,
		BillingPeriodCalculator $calculator,
		ClockInterface $clock,
		LoggerInterface $logger
	) {
		$this->repository = $repository;
		$this->clock      = $clock;
		$this->logger     = $logger;
		$this->calculator = $calculator;
	}

	public function execute( \WC_Order $order ): void {
		if ( $order->needs_payment() || $order->is_paid() === false ) {
			return;
		}

		$subscriptions = $this->repository->find_all_by_order( $order );

		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription->has_status( 'active' ) ) {
				$period = $this->calculator->calculate_activation_period( $subscription, $this->clock->now() );
				$subscription->activate( $period, TransitionContext::system( 'initial_order_paid' ) );
				$this->repository->save( $subscription );

				$this->logger->debug(
					'Subscription "{sid}" successfully activated.',
					[
						'sid' => $subscription->get_id(),
						'oid' => $order->get_id(),
					]
				);
			}
		}
	}
}
