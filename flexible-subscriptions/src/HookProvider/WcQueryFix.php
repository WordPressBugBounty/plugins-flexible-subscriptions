<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use Automattic\WooCommerce\Utilities\OrderUtil;

use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class WcQueryFix implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_order_query_args', $this );
	}

	/**
	 * @param array<string, mixed> $query_vars
	 *
	 * @return array<string, mixed>
	 */
	public function __invoke( $query_vars ) {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return $query_vars;
		}

		/**
		 * Map the 'any' status to wcs_get_subscription_statuses() in HPOS environments.
		 *
		 * In HPOS environments, the 'any' status now maps to wc_get_order_statuses() statuses. Whereas, in
		 * WP Post architecture 'any' meant any status except for ‘inherit’, ‘trash’ and ‘auto-draft’.
		 *
		 * If we're querying for subscriptions, we need to map 'any' to be all valid subscription statuses otherwise it would just search for order statuses.
		 */
		if ( isset( $query_vars['post_type'] ) && '' !== $query_vars['post_type'] ) {
			// OrdersTableQuery::maybe_remap_args() will overwrite `type` with the `post_type` value.
			if ( 'fsb_subscription' !== $query_vars['post_type'] ) {
				return $query_vars;
			}

			// Simplify the type logic.
			$query_vars['type'] = 'fsb_subscription';
			unset( $query_vars['post_type'] );
		}

		if ( isset( $query_vars['type'] ) && 'fsb_subscription' === $query_vars['type'] ) {
			if ( isset( $query_vars['post_status'] ) && '' !== $query_vars['post_status'] ) {
				// OrdersTableQuery::maybe_remap_args() will overwrite `status` with the `post_status` value.
				if ( [ 'any' ] !== (array) $query_vars['post_status'] ) {
					return $query_vars;
				}

				// Simplify the status logic.
				$query_vars['status'] = 'any';
				unset( $query_vars['post_status'] );
			}

			if ( [ 'any' ] === (array) $query_vars['status'] || [ '' ] === (array) $query_vars['status'] ) {
				$query_vars['status'] = array_keys( Status::get_statuses() );
			}
		}

		return $query_vars;
	}
}
