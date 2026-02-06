<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Email;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

/**
 * Add subscription information to any regular order email which included basic informations.
 */
final class OrderAdditionalInfo implements HookProvider {

	private Renderer $renderer;

	private SubscriptionFinder $finder;

	public function __construct( SubscriptionFinder $finder, Renderer $renderer ) {
		$this->renderer = $renderer;
		$this->finder   = $finder;
	}

	public function hooks(): void {
		add_action( 'woocommerce_email_after_order_table', $this, 15, 3 );
	}

	/**
	 * @param \WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function __invoke( $order, $sent_to_admin, $plain_text ): void {
		$subscriptions = $this->finder->find_all_by_order( $order );

		if ( count( $subscriptions ) === 0 ) {
			return;
		}

		$template = ( $plain_text ) ? 'emails/plain/subscription-info' : 'emails/subscription-info';

		$this->renderer->output_render(
			$template,
			[
				'order'          => $order,
				'subscriptions'  => $subscriptions,
				'sent_to_admin'  => $sent_to_admin,
			]
		);
	}
}
