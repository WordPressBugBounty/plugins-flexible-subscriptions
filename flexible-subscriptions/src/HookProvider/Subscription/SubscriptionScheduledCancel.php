<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * When a subscription is scheduled for cancellation (pending cancel),
 * pick up the schedule and end the life of subscription.
 *
 * @see Schedule::cancel()
 */
class SubscriptionScheduledCancel implements HookProvider {

	private SubscriptionFinder $finder;

	private SubscriptionLifecycleManager $lifecycle;

	private LoggerInterface $logger;

	public function __construct( SubscriptionFinder $finder, SubscriptionLifecycleManager $lifecycle, LoggerInterface $logger ) {
		$this->finder    = $finder;
		$this->lifecycle = $lifecycle;
		$this->logger    = $logger;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/cancel', $this );
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		$this->logger->debug( 'Cancelling subscription "{sid}" from schedule...', [ 'sid' => $subscription_id ] );
		$subscription = $this->finder->find( $subscription_id );

		if ( ! $subscription instanceof Subscription ) {
			$this->logger->warning( 'Subscription "{sid}" marked for cancellation not found.', [ 'sid' => $subscription_id ] );
			return;
		}

		$this->lifecycle->transition( $subscription, 'cancelled', TransitionContext::system( 'scheduled_cancellation' ) );
		$this->logger->debug( 'Subscription "{sid}" cancelled from schedule.', [ 'sid' => $subscription_id ] );
	}
}
