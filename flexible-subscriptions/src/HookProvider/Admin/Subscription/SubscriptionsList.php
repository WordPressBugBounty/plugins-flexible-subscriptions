<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class SubscriptionsList implements HookProvider {

	public function hooks(): void {
		add_filter( 'request', [ $this, 'request_query' ] );

		add_filter( 'woocommerce_fsb_subscription_list_table_request', [ $this, 'add_subscription_list_table_query_default_args' ] );
		// add_filter( 'woocommerce_fsb_subscription_list_table_prepare_items_query_args', array( $this, 'filter_subscription_list_table_request_query' ) );
	}

	/**
	 * @param array<string, mixed> $vars
	 * @return array<string, mixed>
	 */
	public function request_query( $vars ) {
		global $typenow;
		if ( $typenow !== 'fsb_subscription' ) {
			return $vars;
		}

		if ( empty( $vars['post_status'] ) ) {
			$vars['post_status'] = array_keys( Status::get_statuses() );
		}
		return $vars;
	}

	/**
	 * @param array<string, mixed> $query_args
	 * @return array<string, mixed>
	 */
	public function add_subscription_list_table_query_default_args( $query_args ) {
		if ( empty( $query_args['status'] ) || ( isset( $_GET['status'] ) && 'all' === $_GET['status'] ) ) {
			$query_args['status'] = array_keys( Status::get_statuses() );
		}

		return $query_args;
	}
}
