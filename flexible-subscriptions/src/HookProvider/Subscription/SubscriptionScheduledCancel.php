<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * When a subscription is scheduled for cancellation (pending cancel),
 * pick up the schedule and end the life of subscription.
 *
 * @see Schedule::cancel()
 */
class SubscriptionScheduledCancel implements HookProvider {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	public function __construct( SubscriptionRepository $repository, ClockInterface $clock, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->clock      = $clock;
		$this->logger     = $logger;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/cancel', $this );
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		$this->logger->debug( 'Cancelling subscription "{sid}" from schedule...', [ 'sid' => $subscription_id ] );
		$subscription = $this->repository->find( $subscription_id );

		if ( ! $subscription instanceof Subscription ) {
			$this->logger->warning( 'Subscription "{sid}" marked for cancellation not found.', [ 'sid' => $subscription_id ] );
			return;
		}

		$subscription->cancel( $this->clock->now(), TransitionContext::system( 'scheduled_cancellation' ) );
		$this->repository->save( $subscription );
		$this->logger->debug( 'Subscription "{sid}" cancelled from schedule.', [ 'sid' => $subscription_id ] );
	}
}
