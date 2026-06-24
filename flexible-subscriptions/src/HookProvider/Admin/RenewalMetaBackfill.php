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

	private const OPTION_KEY   = 'fsb_backfill_subscription_renewal_meta_1_7_17';
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
		update_option(
			self::OPTION_KEY,
			[
				'status' => 'queued',
				'offset' => 0,
			]
		);
	}

	public function run_backfill(): void {
		$state            = get_option( self::OPTION_KEY, [] );
		$offset           = is_array( $state ) ? max( 0, (int) ( $state['offset'] ?? 0 ) ) : 0;
		$subscription_ids = wc_get_orders(
			[
				'type'       => 'fsb_subscription',
				'status'     => 'any',
				'limit'      => self::BATCH_LIMIT,
				'offset'     => $offset,
				'orderby'    => 'ID',
				'order'      => 'ASC',
				'return'     => 'ids',
			]
		);

		if ( empty( $subscription_ids ) ) {
			update_option(
				self::OPTION_KEY,
				[
					'status' => 'completed',
					'offset' => $offset,
				]
			);
			return;
		}

		foreach ( $subscription_ids as $subscription_id ) {
			// phpcs:disable Universal.Arrays
			$orders = wc_get_orders(
				[
					'limit'      => -1,
					'status'     => 'any',
					'type'       => 'shop_order',
					'parent'     => (int) $subscription_id,
					'meta_query' => [
						'relation' => 'OR',
						[
							'key'     => Renewal::META_ORDER_TYPE,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => Renewal::META_SUBSCRIPTION_ID,
							'compare' => 'NOT EXISTS',
						],
					],
				]
			);
			// phpcs:enable Universal.Arrays

			foreach ( $orders as $order ) {
				if ( ! $order instanceof WC_Order ) {
					continue;
				}

				if ( $order->get_created_via( 'edit' ) !== 'subscription' ) {
					continue;
				}

				$updated = false;
				if ( $order->get_meta( Renewal::META_ORDER_TYPE, true ) !== Renewal::ORDER_TYPE_VALUE ) {
					$order->update_meta_data( Renewal::META_ORDER_TYPE, Renewal::ORDER_TYPE_VALUE );
					$updated = true;
				}

				if ( (int) $order->get_meta( Renewal::META_SUBSCRIPTION_ID, true ) !== (int) $subscription_id ) {
					$order->update_meta_data( Renewal::META_SUBSCRIPTION_ID, (string) $subscription_id );
					$updated = true;
				}

				if ( $updated ) {
					$order->save();
				}
			}
		}

		$next_offset = $offset + count( $subscription_ids );
		if ( count( $subscription_ids ) < self::BATCH_LIMIT ) {
			update_option(
				self::OPTION_KEY,
				[
					'status' => 'completed',
					'offset' => $next_offset,
				]
			);
			return;
		}

		update_option(
			self::OPTION_KEY,
			[
				'status' => 'queued',
				'offset' => $next_offset,
			]
		);

		as_enqueue_async_action( self::ACTION_HOOK, [], self::ACTION_GROUP );
	}
}
