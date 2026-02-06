<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Account;

use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class SubscriptionsPage implements HookProvider {

	/** @var SubscriptionFinder **/
	private $finder;

	/** @var Renderer **/
	private $renderer;

	public function __construct(
		SubscriptionFinder $finder,
		Renderer $renderer
	) {
		$this->finder   = $finder;
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		add_action( 'woocommerce_account_fsb-subscriptions_endpoint', [ $this, 'output' ] );
		add_filter( 'woocommerce_endpoint_fsb-subscriptions_title', [ $this, 'set_title' ], 10, 2 );
	}

	/** @param int $current_page */
	public function output( $current_page = 1 ): void {
		$current_page = empty( $current_page ) ? 1 : absint( $current_page );
		$limit        = (int) get_option( 'posts_per_page' );
		$offset       = ( $current_page - 1 ) * $limit;

		$subscriptions       = $this->finder->find_by_customer(
			get_current_user_id(),
			$offset,
			$limit
		);
		$subscriptions_count = $this->finder->count_by_customer(
			get_current_user_id()
		);

		$max_num_pages = ceil( $subscriptions_count / $limit );

		$this->renderer->output_render(
			'myaccount/my-subscriptions',
			[
				'current_page'    => $current_page,
				'subscriptions'   => $subscriptions,
				'max_num_pages'   => $max_num_pages,
			]
		);
	}

	/**
	 * @param string $title
	 * @param string $endpoint
	 */
	public function set_title( $title, $endpoint ): string {
		if ( 'fsb-subscriptions' !== $endpoint ) {
			return $title;
		}

		global $wp;

		if ( ! empty( $wp->query_vars['fsb-subscriptions'] ) ) {
			// translators: placeholder is a page number.
			return sprintf( __( 'Subscriptions (page %d)', 'flexible-subscriptions' ), intval( $wp->query_vars['fsb-subscriptions'] ) );
		}

		return __( 'Subscriptions', 'flexible-subscriptions' );
	}
}
