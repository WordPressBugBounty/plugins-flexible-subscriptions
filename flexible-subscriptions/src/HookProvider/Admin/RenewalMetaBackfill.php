<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Schedules and processes a one-off backfill that links renewal orders with their subscriptions.
 */
final class RenewalMetaBackfill implements HookProvider {

	private const OPTION_KEY   = 'fsb_backfill_subscription_renewal_meta_1_6_12';
	private const ACTION_HOOK  = 'fsb/backfill_subscription_renewal_meta';
	private const ACTION_GROUP = 'flexible-subscriptions';
	private const BATCH_LIMIT  = 50;

	public function hooks(): void {
		add_action( 'admin_init', [ $this, 'maybe_schedule_backfill' ] );
		add_action( self::ACTION_HOOK, [ $this, 'run_backfill' ] );
	}

	public function maybe_schedule_backfill(): void {
		if ( get_option( self::OPTION_KEY ) ) {
			return;
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		as_enqueue_async_action( self::ACTION_HOOK, [], self::ACTION_GROUP );
		update_option( self::OPTION_KEY, 'queued' );
	}

	public function run_backfill(): void {
		// phpcs:disable Universal.Arrays
		$orders = wc_get_orders(
			[
				'limit'      => self::BATCH_LIMIT,
				'status'     => 'any',
				'type'       => 'shop_order',
				'meta_query' => [
					'relation' => 'AND',
					[
						'key'     => Renewal::META_ORDER_TYPE,
						'value'   => Renewal::ORDER_TYPE_VALUE,
						'compare' => '=',
					],
					[
						'key'     => Renewal::META_SUBSCRIPTION_ID,
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);
		// phpcs:enable Universal.Arrays

		if ( empty( $orders ) ) {
			update_option( self::OPTION_KEY, 'completed' );
			return;
		}

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$subscription_id = $order->get_parent_id();

			if ( ! $subscription_id ) {
				continue;
			}

			$order->add_meta_data( Renewal::META_SUBSCRIPTION_ID, $subscription_id, false );
			$order->save();
		}

		if ( count( $orders ) < self::BATCH_LIMIT ) {
			update_option( self::OPTION_KEY, 'completed' );
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_HOOK, [], self::ACTION_GROUP );
		}
	}
}
