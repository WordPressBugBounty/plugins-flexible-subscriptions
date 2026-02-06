<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Renewal\InFlightRenewalOrderFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RenewalFactory;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RequestPaymentStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Process subscription renewal from schedule.
 *
 * @see Schedule::schedule_payment_request()
 */
class PaymentRequestProcessor implements HookProvider {

	private SubscriptionFinder $finder;

	private RequestPaymentStrategy $renewal_strategy;

	private SubscriptionLifecycleManager $lifecycle;

	private LoggerInterface $logger;

	private RenewalFactory $renewal_factory;

	private RenewalLock $renewal_lock;

	private InFlightRenewalOrderFinder $in_flight_finder;

	public function __construct(
		SubscriptionFinder $finder,
		RequestPaymentStrategy $renewal_strategy,
		SubscriptionLifecycleManager $lifecycle,
		RenewalFactory $renewal_factory,
		RenewalLock $renewal_lock,
		InFlightRenewalOrderFinder $in_flight_finder,
		LoggerInterface $logger
	) {
		$this->finder           = $finder;
		$this->renewal_strategy = $renewal_strategy;
		$this->lifecycle        = $lifecycle;
		$this->logger           = $logger;
		$this->renewal_factory  = $renewal_factory;
		$this->renewal_lock     = $renewal_lock;
		$this->in_flight_finder = $in_flight_finder;
	}
	public function hooks(): void {
		// Hook early to create new renewal.
		add_action( 'fsub/subscription/payment_request/process', $this, 1, 1 );
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		try {
			$this->do_process( $subscription_id );
		} catch ( \Throwable $e ) {
			$error_message = $e->getMessage();
			$this->logger->critical(
				'Error processing renewal for subscription "{sid}": {message}',
				[
					'sid'     => $subscription_id,
					'message' => $error_message,
					'trace'   => $e->getTraceAsString(),
				]
			);
			// Clear dismissed notice on new errors.
			delete_option( 'wpdesk_notice_dismiss_flexible-subscriptions-failed-payment-request-notice' );

			set_transient(
				'fsub_payment_request_error_' . $subscription_id,
				[
					'subscription_id' => $subscription_id,
					'message'         => $error_message,
				],
				\WEEK_IN_SECONDS
			);
		}
	}

	private function do_process( $subscription_id ): void {
		$subscription = $this->finder->find( $subscription_id );

		if ( ! $subscription instanceof Subscription ) {
			$this->logger->warning( 'Subscription "{sid}" not found, while processing renewal.', [ 'sid' => $subscription_id ] );
			return;
		}

		if ( ! $subscription->is_active() ) {
			$this->logger->warning( 'Subscription "{sid}" is not active, while processing renewal. Skipping the process...', [ 'sid' => $subscription_id ] );
			return;
		}

		if ( $subscription->is_expired() ) {
			$this->logger->warning( 'Subscription "{sid}" is expired, while processing renewal. Skipping the process...', [ 'sid' => $subscription_id ] );
			$this->lifecycle->transition( $subscription, 'expired', TransitionContext::system( 'renewal_expired' ) );
			return;
		}

		$can_renew = apply_filters( 'fsub/subscription_renewal/should_interrupt_renewal', true, $subscription );

		if ( ! $can_renew ) {
			$this->logger->warning(
				'Creating a new renewal for subscription {sid} have been prevented by external integration',
				[
					'sid' => $subscription_id,
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

		$lock_owner = $this->renewal_lock->acquire( $subscription_id, 600 );
		if ( $lock_owner === null ) {
			$this->logger->warning( 'Failed to acquire renewal lock for subscription "{sid}". Skipping renewal processing...', [ 'sid' => $subscription_id ] );
			return;
		}

		try {
			// Re-fetch fresh subscription while holding the lock.
			$subscription = $this->finder->find( $subscription_id );
			if ( ! $subscription instanceof Subscription ) {
				return;
			}

			$this->logger->debug(
				'Changing subscription "{sid}" status to "{status}"...',
				[
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
					'status'       => $status,
				]
			);
			$this->lifecycle->transition( $subscription, $status, TransitionContext::system( 'renewal_awaiting' ) );
			$this->logger->debug(
				'Subscription "{sid}" status changed to "{status}".',
				[
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
					'status'       => $status,
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
						'sid'   => $subscription_id,
						'cycle' => $billing_cycle,
					]
				);
			} else {
				$created_new = true;
				$this->logger->debug(
					'Creating a new renewal for subscription "{sid}"...',
					[
						'sid'          => $subscription_id,
						'subscription' => (string) $subscription,
					]
				);
				$renewal = $this->renewal_factory->create_for( $subscription );
			}

			$this->logger->debug(
				'Choosing a renewal strategy for renewal coming from subscription "{sid}"...',
				[
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
					'renewal'      => $renewal->get_id(),
				]
			);
			if ( $this->renewal_strategy->supports( $subscription, $renewal->get_order() ) ) {
				$this->renewal_strategy->request_payment( $renewal->get_order() );
				$this->logger->debug(
					'Renewal for subscription "{sid}" handled by renewal strategy',
					[
						'sid'          => $subscription_id,
						'subscription' => (string) $subscription,
						'renewal'      => $renewal->get_id(),
					]
				);
			} else {
				$this->logger->debug(
					'No renewal strategy could handle a renewal coming from subscription "{sid}".',
					[
						'sid'          => $subscription_id,
						'subscription' => (string) $subscription,
						'renewal'      => $renewal->get_id(),
					]
				);
			}

			$this->logger->debug(
				'Recording recent renewal for subscription "{sid}"',
				[
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
					'renewal'      => $renewal->get_id(),
				]
			);

			// Fetch fresh subscription, we might have changed it in the meantime.
			$subscription = $this->finder->find( $subscription_id );
			if ( ! $subscription instanceof Subscription ) {
				return;
			}

			$subscription->record_payment_request( $renewal->get_order() );

			$this->logger->debug(
				'Recorded recent renewal for subscription "{sid}"',
				[
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
					'renewal'      => $renewal->get_id(),
				]
			);

			$subscription->save();
			$renewal->save();

			$this->logger->debug(
				'Processed renewal "{rid}" for subscription "{sid}"',
				[
					'rid'          => $renewal->get_id(),
					'sid'          => $subscription_id,
					'subscription' => (string) $subscription,
				]
			);

			if ( $created_new ) {
				do_action( 'fsub/subscription_renewal/created', $renewal->get_order(), $subscription );
			}
		} finally {
			$this->renewal_lock->release( $subscription_id, $lock_owner );
		}
	}
}
