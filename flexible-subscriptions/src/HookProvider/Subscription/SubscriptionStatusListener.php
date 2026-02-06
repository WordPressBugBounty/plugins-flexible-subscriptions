<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class SubscriptionStatusListener implements HookProvider {

	private SubscriptionLifecycleManager $lifecycle;

	private LoggerInterface $logger;

	public function __construct( SubscriptionLifecycleManager $lifecycle, LoggerInterface $logger ) {
		$this->lifecycle = $lifecycle;
		$this->logger    = $logger;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/status/updated', $this, 10, 3 );
	}

	public function __invoke( Subscription $subscription, string $new_status = '', ?string $previous_status = null ): void {
		if ( method_exists( $subscription, 'is_lifecycle_transition_in_progress' ) && $subscription->is_lifecycle_transition_in_progress() ) {
			return;
		}

		$this->logger->debug(
			'Reacting to subscription "{sid}" status change...',
			[
				'sid'            => $subscription->get_id(),
				'subscription'   => (string) $subscription,
				'current_status' => $new_status !== '' ? $new_status : $subscription->get_status(),
			]
		);

		$this->lifecycle->handle_external_status_change(
			$subscription,
			$new_status !== '' ? $new_status : $subscription->get_status(),
			$previous_status,
			TransitionContext::manual( 'status_hook' )
		);
	}
}
