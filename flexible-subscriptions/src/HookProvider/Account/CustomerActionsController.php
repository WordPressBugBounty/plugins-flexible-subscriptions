<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Account;

use WPDesk\FlexibleSubscriptions\Subscription\Lifecycle\LifecycleOrchestrator;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class CustomerActionsController implements HookProvider {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	public function __construct( SubscriptionRepository $repository, ClockInterface $clock, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->clock      = $clock;
		$this->logger     = $logger;
	}

	public function hooks(): void {
		add_action( 'admin_post_customer_cancel_subscription', $this );
	}

	// TODO: add exit codes to match with messages after processing. Or wc_add_notice... should work
	// Wrong... wc_add_notice is undefined in context of this hook. Maybe it's better to rely on {@see \WC_Order::get_cancel_order_url()}
	public function __invoke(): void {
		check_admin_referer( 'fsb_cancel_subscription' );

		if ( ! isset( $_GET['id'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$this->logger->debug( 'Initiaing user subscription cancellation...' );

		$subscription = $this->repository->find( absint( $_GET['id'] ) );

		if ( ! $subscription instanceof Subscription ) {
			$this->logger->debug( 'Cannot cancel subscription as subscription "{sid}" not found.', [ 'sid' => absint( $_GET['id'] ) ] );
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		if ( $subscription->get_user_id() !== get_current_user_id() ) {
			$this->logger->debug(
				'Subscription "{sid}" belongs to another user.',
				[
					'sid' => $subscription->get_id(),
				]
			);
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$this->logger->debug(
			'Cancelling subscription "{sid}" on user request.',
			[
				'sid' => $subscription->get_id(),
			]
		);

		$subscription->cancel( $this->clock->now(), TransitionContext::manual( 'customer_cancel' ) );
		$subscription->add_order_note( __( 'Subscription cancelled by customer.', 'flexible-subscriptions' ), 0, true );
		$this->repository->save( $subscription );

		$this->logger->debug( 'Subscription cancelled succesfully. Redirecting to subscription\'s page.' );

		wp_safe_redirect( $subscription->get_view_order_url() );
		exit;
	}
}
