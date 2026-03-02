<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Actions\GenerateRenewalOrder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Process subscription renewal from schedule.
 *
 * @see Schedule::schedule_payment_request()
 */
class PaymentRequestProcessor implements HookProvider {

	private GenerateRenewalOrder $generate_renewal_order;

	private SubscriptionRepository $repository;

	private LoggerInterface $logger;

	public function __construct(
		GenerateRenewalOrder $generate_renewal_order,
		SubscriptionRepository $repository,
		LoggerInterface $logger
	) {
		$this->generate_renewal_order = $generate_renewal_order;
		$this->repository             = $repository;
		$this->logger                 = $logger;
	}

	public function hooks(): void {
		// Hook early to create new renewal.
		add_action( 'fsub/subscription/payment_request/process', $this, 1, 1 );
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		try {
			$subscription = $this->repository->find( $subscription_id );
			if ( ! $subscription instanceof Subscription ) {
				return;
			}
			$this->generate_renewal_order->execute( $subscription );
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
}
