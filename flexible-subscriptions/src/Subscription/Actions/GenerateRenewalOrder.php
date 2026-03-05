<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Subscription\Actions;

use WPDesk\FlexibleSubscriptions\Subscription\Lifecycle\LifecycleSkipReason;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\InFlightRenewalOrderFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RenewalFactory;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RequestPaymentStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

final class GenerateRenewalOrder {

	private SubscriptionRepository $repository;

	private RequestPaymentStrategy $renewal_strategy;

	private UpdateSubscriptionStatus $update_status;

	private LoggerInterface $logger;

	private RenewalFactory $renewal_factory;

	private RenewalLock $renewal_lock;

	private InFlightRenewalOrderFinder $in_flight_finder;

	private ClockInterface $clock;

	public function __construct(
		SubscriptionRepository $repository,
		RequestPaymentStrategy $renewal_strategy,
		UpdateSubscriptionStatus $update_status,
		RenewalFactory $renewal_factory,
		RenewalLock $renewal_lock,
		InFlightRenewalOrderFinder $in_flight_finder,
		ClockInterface $clock,
		LoggerInterface $logger
	) {
		$this->repository       = $repository;
		$this->renewal_strategy = $renewal_strategy;
		$this->update_status    = $update_status;
		$this->logger           = $logger;
		$this->renewal_factory  = $renewal_factory;
		$this->renewal_lock     = $renewal_lock;
		$this->in_flight_finder = $in_flight_finder;
		$this->clock            = $clock;
	}

	public function execute( Subscription $subscription ): void {
		if ( ! $subscription->is_active() ) {
			$this->logger->warning(
				'Subscription "{sid}" is not active, while processing renewal. Skipping the process...',
				[
					'sid'         => $subscription->get_id(),
					'skip_reason' => LifecycleSkipReason::SUBSCRIPTION_NOT_ACTIVE,
				]
			);
			return;
		}

		if ( $subscription->is_expired( $this->clock->now() ) ) {
			$this->logger->warning(
				'Subscription "{sid}" is expired, while processing renewal. Skipping the process...',
				[
					'sid'         => $subscription->get_id(),
					'skip_reason' => LifecycleSkipReason::SUBSCRIPTION_EXPIRED,
				]
			);
			$subscription->expire( $this->clock->now(), TransitionContext::system( 'renewal_expired' ) );
			return;
		}

		$can_renew = apply_filters( 'fsub/subscription_renewal/should_interrupt_renewal', true, $subscription );

		if ( ! $can_renew ) {
			$this->logger->warning(
				'Creating a new renewal for subscription {sid} have been prevented by external integration',
				[
					'sid'         => $subscription->get_id(),
					'skip_reason' => LifecycleSkipReason::RENEWAL_INTERRUPTED_BY_INTEGRATION,
				]
			);
			return;
		}

		/**
		 * Filters the subscription status when awaiting payment.
		 *
		 * @param string $status Default 'on-hold' status
		 * @param Subscription $subscription The subscription object
		 * @return string
		 */
		$status = apply_filters( 'fsub/subscription/status_awaiting', 'on-hold', $subscription );

		$lock_owner = $this->renewal_lock->acquire( $subscription->get_id(), 600 );
		if ( $lock_owner === null ) {
			$this->logger->warning(
				'Failed to acquire renewal lock for subscription "{sid}". Skipping renewal processing...',
				[
					'sid'         => $subscription->get_id(),
					'skip_reason' => LifecycleSkipReason::RENEWAL_LOCK_NOT_ACQUIRED,
				]
			);
			return;
		}

		try {
			// Re-fetch a fresh subscription while holding the lock.
			$subscription = $this->repository->find( $subscription->get_id() );
			if ( ! $subscription instanceof Subscription ) {
				return;
			}

			$this->logger->debug(
				'Changing subscription "{sid}" status to "{status}"...',
				[
					'sid'    => $subscription->get_id(),
					'status' => $status,
				]
			);
			$this->update_status->execute( $subscription, $status, TransitionContext::system( 'renewal_awaiting' ) );
			$this->logger->debug(
				'Subscription "{sid}" status changed to "{status}".',
				[
					'sid'    => $subscription->get_id(),
					'status' => $status,
				]
			);

			$billing_cycle = $subscription->get_billing_cycle();

			$created_new = false;
			$renewal     = $this->in_flight_finder->find( $subscription, $billing_cycle );
			if ( $renewal instanceof Renewal ) {
				$this->logger->info(
					'Reusing in-flight renewal order "{oid}" for subscription "{sid}" (cycle "{cycle}").',
					[
						'oid'   => $renewal->get_id(),
						'sid'   => $subscription->get_id(),
						'cycle' => $billing_cycle,
					]
				);
			} else {
				$created_new = true;
				$this->logger->debug(
					'Creating a new renewal for subscription "{sid}"...',
					[
						'sid' => $subscription->get_id(),
					]
				);
				$renewal = $this->renewal_factory->create_for( $subscription );
			}

			$this->logger->debug(
				'Choosing a renewal strategy for renewal coming from subscription "{sid}"...',
				[
					'sid'     => $subscription->get_id(),
					'renewal' => $renewal->get_id(),
				]
			);

			// Record the renewal order before requesting payment to avoid a race
			// where order status hooks fire before recent_payment_request_id is updated.
			$subscription->record_payment_request( $renewal->get_order() );

			$this->repository->save( $subscription );

			// Persist renewal metadata before handing the order to the gateway.
			$renewal->save();
		} finally {
			// Release the lock before requesting payment. The critical section is
			// over: subscription is on-hold (prevents duplicate renewals via
			// is_active() guard) and the renewal order is fully persisted.
			// Releasing here allows ProcessPaidRenewal to acquire the lock when
			// a synchronous gateway (e.g. Stripe) completes payment in the same request.
			$this->renewal_lock->release( $subscription->get_id(), $lock_owner );
		}

		if ( $this->renewal_strategy->supports( $subscription, $renewal->get_order() ) ) {
			$this->renewal_strategy->request_payment( $renewal->get_order() );
			$this->logger->debug(
				'Renewal for subscription "{sid}" handled by renewal strategy',
				[
					'sid'     => $subscription->get_id(),
					'renewal' => $renewal->get_id(),
				]
			);
		} else {
			$this->logger->debug(
				'No renewal strategy could handle a renewal coming from subscription "{sid}".',
				[
					'sid'     => $subscription->get_id(),
					'renewal' => $renewal->get_id(),
				]
			);
		}

		$this->logger->debug(
			'Processed renewal "{rid}" for subscription "{sid}"',
			[
				'rid' => $renewal->get_id(),
				'sid' => $subscription->get_id(),
			]
		);

		if ( $created_new ) {
			do_action( 'fsub/subscription_renewal/created', $renewal->get_order(), $subscription );
		}
	}
}
