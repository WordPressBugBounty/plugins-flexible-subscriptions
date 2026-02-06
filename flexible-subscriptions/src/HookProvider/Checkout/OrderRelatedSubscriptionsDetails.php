<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Checkout;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class OrderRelatedSubscriptionsDetails implements HookProvider {

	/** @var Renderer */
	private $renderer;

	/** @var SubscriptionFinder */
	private $subscription_finder;


	public function __construct(
		SubscriptionFinder $subscription_finder,
		Renderer $renderer
	) {
		$this->renderer            = $renderer;
		$this->subscription_finder = $subscription_finder;
	}

	public function hooks(): void {
		add_action( 'woocommerce_order_details_after_order_table', $this, 10 );
	}

	/** @param \WC_Order $order */
	public function __invoke( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$subscriptions = $this->subscription_finder->find_all_by_order( $order );

		if ( count( $subscriptions ) === 0 ) {
			return;
		}

		$this->renderer->output_render(
			'myaccount/related-subscriptions',
			[ 'subscriptions' => $subscriptions ]
		);
	}
}
