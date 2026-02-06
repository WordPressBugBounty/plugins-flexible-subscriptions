<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class SubscriptionFilter implements HookProvider {

	/** @var SubscriptionFinder */
	private $subscription_finder;

	public function __construct(
		SubscriptionFinder $subscription_finder
	) {
		$this->subscription_finder = $subscription_finder;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_fsb_subscription_list_table_prepare_items_query_args', [ $this, 'filter_query' ] );
	}

	public function filter_query( array $query_args ): array {
		if ( ! isset( $_GET['_customer_user'] ) || ! $_GET['_customer_user'] > 0 ) {
			return $query_args;
		}

		$customer_id = absint( $_GET['_customer_user'] );

		return array_merge_recursive(
			$query_args,
			[
				'post__in' => array_map(
					fn ( Subscription $subscription ) => $subscription->get_id(),
					$this->subscription_finder->find_by_customer( $customer_id )
				),
			]
		);
	}
}
