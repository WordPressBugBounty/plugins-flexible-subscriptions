<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinderInterface;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionUpdater;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class SubscriptionsList implements HookProvider {
	private const BULK_ACTION_PREFIX = 'fsub_status_';
	private const BULK_ACTION_STATUSES = [
		Status::ACTIVE,
		Status::ON_HOLD,
		Status::PENDING_CANCEL,
		Status::CANCELLED,
		Status::EXPIRED,
	];

	private SubscriptionFinderInterface $finder;

	private SubscriptionUpdater $updater;

	public function __construct( SubscriptionFinderInterface $finder, SubscriptionUpdater $updater ) {
		$this->finder  = $finder;
		$this->updater = $updater;
	}

	public function hooks(): void {
		add_filter( 'request', [ $this, 'request_query' ] );
		add_action( 'admin_notices', [ $this, 'bulk_action_notices' ] );

		add_filter( 'woocommerce_fsb_subscription_list_table_request', [ $this, 'add_subscription_list_table_query_default_args' ] );

		foreach ( [ 'edit-fsb_subscription', 'woocommerce_page_wc-orders--' . Subscription::OBJECT_TYPE ] as $screen_id ) {
			add_filter( "bulk_actions-{$screen_id}", [ $this, 'bulk_actions' ] );
			add_filter( "handle_bulk_actions-{$screen_id}", [ $this, 'handle_bulk_actions' ], 10, 3 );
		}
		// add_filter( 'woocommerce_fsb_subscription_list_table_prepare_items_query_args', array( $this, 'filter_subscription_list_table_request_query' ) );
	}

	/** @param array<string, string> $actions */
	public function bulk_actions( array $actions ): array {
		foreach ( array_keys( $actions ) as $action ) {
			if ( strpos( $action, 'mark_' ) === 0 ) {
				unset( $actions[ $action ] );
			}
		}

		foreach ( self::BULK_ACTION_STATUSES as $status ) {
			$status_slug = substr( $status, 3 );
			$actions[ self::BULK_ACTION_PREFIX . $status_slug ] = sprintf(
				/* translators: %s: subscription status */
				__( 'Change status to %s', 'flexible-subscriptions' ),
				Status::nice_name( $status )
			);
		}

		return $actions;
	}

	/**
	 * @param string $redirect_to
	 * @param string $action
	 * @param int[]  $ids
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ): string {
		if ( strpos( $action, self::BULK_ACTION_PREFIX ) !== 0 ) {
			return $redirect_to;
		}

		$new_status = substr( $action, strlen( self::BULK_ACTION_PREFIX ) );
		if ( ! in_array( 'wc-' . $new_status, self::BULK_ACTION_STATUSES, true ) ) {
			return $redirect_to;
		}

		$changed  = 0;
		$failures = [];
		foreach ( array_map( 'absint', $ids ) as $id ) {
			$subscription = $this->finder->find( $id );
			if ( ! $subscription instanceof Subscription || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}

			$old_status = $subscription->get_status();
			$this->updater->handle_status_change( $subscription, $new_status );
			if ( $old_status !== $subscription->get_status() ) {
				++$changed;
			} else {
				$failures[ $old_status ] = ( $failures[ $old_status ] ?? 0 ) + 1;
			}
		}

		return add_query_arg(
			[
				'fsub_bulk_status'   => $new_status,
				'fsub_bulk_changed'  => $changed,
				'fsub_bulk_failures' => $failures,
			],
			remove_query_arg( [ 'bulk_action', 'changed', 'ids', 'fsub_bulk_status', 'fsub_bulk_changed', 'fsub_bulk_failures' ], $redirect_to )
		);
	}

	public function bulk_action_notices(): void {
		if ( empty( $_GET['fsub_bulk_status'] ) ) {
			return;
		}

		$new_status = sanitize_key( wp_unslash( $_GET['fsub_bulk_status'] ) );
		if ( ! isset( Status::get_statuses()[ 'wc-' . $new_status ] ) ) {
			return;
		}

		$changed  = isset( $_GET['fsub_bulk_changed'] ) ? absint( $_GET['fsub_bulk_changed'] ) : 0;
		$messages = [
			sprintf(
				/* translators: %d: number of subscriptions */
				_n( '%d subscription status changed.', '%d subscription statuses changed.', $changed, 'flexible-subscriptions' ),
				$changed
			),
		];

		$failures = isset( $_GET['fsub_bulk_failures'] ) && is_array( $_GET['fsub_bulk_failures'] ) ? wc_clean( wp_unslash( $_GET['fsub_bulk_failures'] ) ) : [];
		foreach ( $failures as $old_status => $count ) {
			$count      = absint( $count );
			$old_status = sanitize_key( $old_status );
			if ( $count === 0 || ! isset( Status::get_statuses()[ 'wc-' . $old_status ] ) ) {
				continue;
			}

			$messages[] = sprintf(
				/* translators: 1: number of subscriptions, 2: current status, 3: requested status */
				_n(
					'%1$d subscription could not be changed from %2$s to %3$s.',
					'%1$d subscriptions could not be changed from %2$s to %3$s.',
					$count,
					'flexible-subscriptions'
				),
				$count,
				Status::nice_name( $old_status ),
				Status::nice_name( $new_status )
			);
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			count( $messages ) > 1 ? 'warning' : 'success',
			implode( '<br>', array_map( 'esc_html', $messages ) )
		);
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
