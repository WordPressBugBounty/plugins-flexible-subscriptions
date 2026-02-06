<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\LifecycleTransitionRequest;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class LifecycleTransitionListener implements HookProvider {

	private const ACTION = 'fsub/_internal/subscription/lifecycle/transition';

	private SubscriptionLifecycleManager $lifecycle;

	public function __construct( SubscriptionLifecycleManager $lifecycle ) {
		$this->lifecycle = $lifecycle;
	}

	public function hooks(): void {
		add_action( self::ACTION, $this );
	}

	public function __invoke( LifecycleTransitionRequest $request ): void {
		if ( $request->handled ) {
			return;
		}

		$request->handled = $this->lifecycle->transition( $request->subscription, $request->to, $request->context );
	}
}
