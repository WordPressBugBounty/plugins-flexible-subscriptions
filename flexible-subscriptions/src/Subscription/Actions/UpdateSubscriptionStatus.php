<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Subscription\Actions;

use WPDesk\FlexibleSubscriptions\Subscription\BillingPeriodCalculator;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;

/**
 * Updates the status of a subscription, ensuring that all necessary side effects are handled correctly.
 *
 * Used primary for external status updates, such as those triggered by payment gateways or manual admin actions.
 */
final class UpdateSubscriptionStatus {

	private SubscriptionRepository $repository;

	private BillingPeriodCalculator $period_calculator;

	private ClockInterface $clock;

	public function __construct(
		SubscriptionRepository $repository,
		BillingPeriodCalculator $period_calculator,
		ClockInterface $clock
	) {
		$this->repository        = $repository;
		$this->period_calculator = $period_calculator;
		$this->clock             = $clock;
	}

	public function execute( Subscription $subscription, string $new_status, ?TransitionContext $context = null ): bool {
		$from = $subscription->get_status();
		$to   = $subscription->resolve_transition_target_status_from( $new_status, $from );

		if ( $from === $to || ! $subscription->can_transition_from_to( $from, $new_status ) ) {
			return false;
		}

		$context ??= TransitionContext::system();

		switch ( $to ) {
			case 'active':
				$period = $this->period_calculator->calculate_activation_period( $subscription, $this->clock->now() );
				$subscription->activate( $period, $context );
				break;
			case 'on-hold':
				$subscription->pause( $context );
				break;
			case 'pending-cancel':
			case 'cancelled':
				$subscription->cancel( $this->clock->now(), $context );
				break;
			case 'expired':
				$subscription->expire( $this->clock->now(), $context );
				break;
			default:
				$subscription->update_status( $to, $context->note, $context->manual );
		}

		$this->repository->save( $subscription );

		return true;
	}
}
