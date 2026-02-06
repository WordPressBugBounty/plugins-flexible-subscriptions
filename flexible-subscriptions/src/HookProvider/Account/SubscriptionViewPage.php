<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Account;

use WPDesk\FlexibleSubscriptions\Formatting\Price\PaymentRequestTotalFormatter;
use WPDesk\FlexibleSubscriptions\Subscription\Payment\PaymentRequestFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class SubscriptionViewPage implements HookProvider {

	/** @var SubscriptionFinder **/
	private $finder;

	/** @var Renderer **/
	private $renderer;

	/** @var PaymentRequestFinder */
	private $payment_request_finder;

	public function __construct(
		SubscriptionFinder $finder,
		PaymentRequestFinder $payment_request_finder,
		Renderer $renderer
	) {
		$this->finder                 = $finder;
		$this->renderer               = $renderer;
		$this->payment_request_finder = $payment_request_finder;
	}

	public function hooks(): void {
		add_action( 'woocommerce_account_view-fsb-subscription_endpoint', [ $this, 'output' ] );
		add_filter( 'woocommerce_endpoint_view-fsb-subscription_title', [ $this, 'set_title' ], 10, 2 );
	}

	public function output(): void {
		$subscription = $this->finder->find( (int) get_query_var( 'view-fsb-subscription' ) );

		if ( ! $subscription instanceof Subscription || ! current_user_can( 'view_order', $subscription->get_id() ) ) {
			printf(
				'<div class="woocommerce-error">%s</div>',
				esc_html__( 'Invalid subscription.', 'flexible-subscriptions' )
			);
			return;
		}

		$this->renderer->output_render(
			'myaccount/view-subscription',
			[
				'subscription'                    => $subscription,
				'payment_requests'                => $this->payment_request_finder->find_for_subscription( $subscription ),
			]
		);
	}

	/**
	 * @param string $title
	 * @param string $endpoint
	 */
	public function set_title( $title, $endpoint ): string {
		if ( 'view-fsb-subscription' !== $endpoint ) {
			return $title;
		}

		global $wp;
		$subscription = $this->finder->find( (int) $wp->query_vars['view-fsb-subscription'] );

		if ( $subscription instanceof Subscription ) {
			// translators: placeholder is a subscription ID.
			return sprintf( _x( 'Subscription #%s', 'hash before order number', 'flexible-subscriptions' ), $subscription->get_order_number() );
		}

		return esc_html__( 'Subscriptions', 'flexible-subscriptions' );
	}
}
