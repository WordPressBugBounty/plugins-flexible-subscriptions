<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Email;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class NewOrderSubscriptionInfo implements HookProvider {

	private SubscriptionFinder $finder;

	private Renderer $renderer;

	public function __construct(
		SubscriptionFinder $finder,
		Renderer $renderer
	) {
		$this->finder   = $finder;
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		add_action( 'woocommerce_email_after_order_table', $this, 15, 3 );
	}

	/**
	 * @param \WC_Order $order
	 * @param bool $is_admin_email
	 * @param bool $plaintext
	 */
	public function __invoke( $order, $is_admin_email, $plaintext = false ): void {
		$subscriptions = $this->finder->find_all_by_order( $order );

		if ( count( $subscriptions ) === 0 ) {
			return;
		}

		$template = ( $plaintext ) ? 'emails/plain/subscription-info' : 'emails/subscription-info';

		$this->renderer->output_render(
			$template,
			[
				'order'          => $order,
				'subscriptions'  => $subscriptions,
				'is_admin_email' => $is_admin_email,
			],
		);
	}
}
