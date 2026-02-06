<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

final class RelatedSubscription implements HookProvider {

	private SubscriptionFinder $finder;

	private Renderer $renderer;

	public function __construct( SubscriptionFinder $finder, Renderer $renderer ) {
		$this->finder   = $finder;
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		add_action( 'woocommerce_admin_order_data_after_order_details', $this );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function __invoke( $order ): void {
		if ( $order instanceof Subscription ) {
			return;
		}

		$subscription = $this->finder->find( $order->get_parent_id() );

		if ( $subscription instanceof Subscription ) {
			$this->renderer->output_render(
				'order/related-subscription',
				[
					'subscriptions' => [ $subscription ],
				]
			);
			return;
		}

		$subscriptions = $this->finder->find_all_by_order( $order );

		if ( empty( $subscriptions ) ) {
			return;
		}

		$this->renderer->output_render(
			'order/related-subscription',
			[
				'subscriptions' => array_values( $subscriptions ),
			]
		);
	}
}
