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
 * When a subscription reaches its end date, transition it to expired.
 */
final class SubscriptionScheduledExpire implements HookProvider {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	public function __construct( SubscriptionRepository $repository, ClockInterface $clock, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->clock      = $clock;
		$this->logger     = $logger;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/expire', $this );
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		$this->logger->debug( 'Expiring subscription "{sid}" from schedule...', [ 'sid' => $subscription_id ] );
		$subscription = $this->repository->find( $subscription_id );

		if ( ! $subscription instanceof Subscription ) {
			$this->logger->warning( 'Subscription "{sid}" marked for expiration not found.', [ 'sid' => $subscription_id ] );
			return;
		}

		$end_date = $subscription->get_end_date();
		if ( ! $end_date instanceof \DateTimeInterface || $end_date > $this->clock->now() ) {
			$this->logger->debug( 'Skipping scheduled expiration for subscription "{sid}" because it is not due yet.', [ 'sid' => $subscription_id ] );
			return;
		}

		$subscription->expire( $this->clock->now(), TransitionContext::system( 'scheduled_expiration' ) );
		$this->repository->save( $subscription );
		$this->logger->debug( 'Subscription "{sid}" expired from schedule.', [ 'sid' => $subscription_id ] );
	}
}
