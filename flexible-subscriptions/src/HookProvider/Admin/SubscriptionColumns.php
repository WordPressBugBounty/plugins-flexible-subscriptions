<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPDesk\FlexibleSubscriptions\Formatting\Price\PriceFormat;
use WPDesk\FlexibleSubscriptions\Subscription\Payment\PaymentRequestFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\HumanFriendlyFormat;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\Iso8601Format;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\WordPressDateTime;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class SubscriptionColumns implements HookProvider {

	/** @var SubscriptionFinder */
	private $subscription_finder;

	/** @var Renderer */
	private $renderer;

	/** @var PaymentRequestFinder */
	private $payment_request_finder;

	public function __construct(
		SubscriptionFinder $subscription_finder,
		PaymentRequestFinder $payment_request_finder,
		Renderer $renderer
	) {
		$this->subscription_finder    = $subscription_finder;
		$this->renderer               = $renderer;
		$this->payment_request_finder = $payment_request_finder;
	}

	public function hooks(): void {
		add_filter( 'manage_edit-fsb_subscription_columns', [ $this, 'shop_subscription_columns' ] );
		add_filter( 'manage_edit-fsb_subscription_sortable_columns', [ $this, 'shop_subscription_sortable_columns' ] );
		add_action( 'manage_fsb_subscription_posts_custom_column', [ $this, 'render_shop_subscription_columns' ], 2 );

		add_filter( 'woocommerce_fsb_subscription_list_table_columns', [ $this, 'shop_subscription_columns' ] );
		add_filter( 'woocommerce_fsb_subscription_list_table_sortable_columns', [ $this, 'shop_subscription_sortable_columns' ] );
		add_action( 'woocommerce_fsb_subscription_list_table_custom_column', [ $this, 'render_shop_subscription_columns' ], 2, 2 );
	}

	/**
	 * Define custom columns for subscription
	 *
	 * Column names that have a corresponding `WC_Order` column use the `order_` prefix here
	 * to take advantage of core WooCommerce assets, like JS/CSS.
	 *
	 * @param  array<string, string> $existing_columns
	 * @return array<string, string>
	 */
	public function shop_subscription_columns( array $existing_columns ): array {
		return [
			'cb'                        => '<input type="checkbox" />',
			'status'                    => __( 'Status', 'flexible-subscriptions' ),
			'order_title'               => __( 'Subscription', 'flexible-subscriptions' ),
			'order_items'               => __( 'Items', 'flexible-subscriptions' ),
			'recurring_total'           => __( 'Total', 'flexible-subscriptions' ),
			'start_date'                => __( 'Start Date', 'flexible-subscriptions' ),
			'trial_end_date'            => __( 'Trial End', 'flexible-subscriptions' ),
			'current_period_end'        => __( 'Next Payment', 'flexible-subscriptions' ),
			'last_payment_request_date' => __( 'Last Transaction', 'flexible-subscriptions' ),
			'end_date'                  => __( 'End Date', 'flexible-subscriptions' ),
			'orders'                    => _x( 'Related orders', 'number of payment requests linked to a subscription', 'flexible-subscriptions' ),
		];
	}

	/**
	 * Make columns sortable
	 *
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function shop_subscription_sortable_columns( array $columns ): array {
		$sortable_columns = [
			'order_title'               => 'ID',
			'recurring_total'           => 'order_total',
			'start_date'                => 'start_date',
			'trial_end_date'            => 'trial_end_date',
			'current_period_end'        => 'current_period_end',
			'last_payment_request_date' => 'last_payment_date',
			'end_date'                  => 'end_date',
		];

		return wp_parse_args( $sortable_columns, $columns );
	}

	/**
	 * @param string $column
	 */
	public function render_shop_subscription_columns( $column, $order = null ): void {
		global $post;

		if ( $order instanceof \WC_Order ) {
			$id = $order->get_id();
		} else {
			$id = $post->ID;
		}

		$the_subscription = $this->subscription_finder->find( $id );

		if ( ! $the_subscription instanceof Subscription ) {
			if ( 'order_title' === $column ) {
				$this->renderer->output_render(
					'subscription/table/not-found',
					[ 'subscription_id' => $id ]
				);
			} else {
				echo '&mdash;';
			}

			return;
		}

		$column_content = '';
		if ( is_callable( [ $this, "column_$column" ] ) ) {
			// @phpstan-ignore method.dynamicName
			$column_content = $this->{"column_$column"}( $the_subscription );
		}

		echo wp_kses(
			$column_content,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				[
					'time' => [
						'datetime' => [],
						'title'    => [],
					],
				]
			)
		);
	}

	private function column_start_date( Subscription $the_subscription ): string {
		return $this->render_date_column( $the_subscription->get_start_date() );
	}

	private function column_trial_end_date( Subscription $the_subscription ): string {
		return $this->render_date_column( $the_subscription->get_trial_end_date() );
	}

	private function column_current_period_end( Subscription $the_subscription ): string {
		if ( $the_subscription->is_finalized() ) {
			return __( 'N/A', 'flexible-subscriptions' );
		}

		return $this->render_date_column( $the_subscription->get_current_period_end() );
	}

	private function column_last_payment_request_date( Subscription $the_subscription ): string {
		$payment_request_id = $the_subscription->get_recent_payment_request_id();
		$payment_request    = $this->payment_request_finder->find( $payment_request_id );

		if ( $payment_request instanceof \WC_Order ) {
			return $this->render_date_column( $payment_request->get_date_created() );
		}

		return '&mdash;';
	}

	private function column_end_date( Subscription $the_subscription ): string {
		return $this->render_date_column( $the_subscription->get_end_date() );
	}

	private function render_date_column( ?\DateTimeInterface $date ): string {
		if ( ! $date instanceof \DateTimeInterface ) {
			return '&mdash;';
		}

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			new Iso8601Format( $date ),
			new WordPressDateTime( $date ),
			new HumanFriendlyFormat( $date ),
		);
	}

	private function column_orders( Subscription $the_subscription ): string {
		$orders_table_url = OrderUtil::custom_orders_table_usage_is_enabled() ? 'admin.php?page=wc-orders&status=all' : 'edit.php?post_type=shop_order&post_status=all';

		return sprintf(
			'<a href="%s">%s</a>',
			admin_url( $orders_table_url . '&_subscription_related_orders=' . absint( $the_subscription->get_id() ) ),
			count( $this->payment_request_finder->find_for_subscription( $the_subscription ) )
		);
	}

	private function column_status( Subscription $the_subscription ): string {
		global $wp_list_table;

		$column_content = sprintf(
			'<mark class="subscription-status order-status status-%1$s %1$s tips" data-tip="%2$s"><span>%3$s</span></mark>',
			sanitize_title( $the_subscription->get_status() ),
			Status::nice_name( $the_subscription->get_status() ),
			Status::nice_name( $the_subscription->get_status() ),
		);

		$actions = [];

		$action_url = add_query_arg(
			[
				'post'     => $the_subscription->get_id(),
				// Using the bulk actions nonce name as defined in WP core.
				'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
			]
		);

		if ( isset( $_REQUEST['status'] ) ) {
			$action_url = add_query_arg( [ 'status' => sanitize_key( wp_unslash( $_REQUEST['status'] ) ) ], $action_url );
		}

		$all_statuses = [
			'active'    => __( 'Reactivate', 'flexible-subscriptions' ),
			'on-hold'   => __( 'Suspend', 'flexible-subscriptions' ),
			'cancelled' => _x( 'Cancel', 'an action on a subscription', 'flexible-subscriptions' ),
			'trash'     => __( 'Trash', 'flexible-subscriptions' ),
			'deleted'   => __( 'Delete Permanently', 'flexible-subscriptions' ),
		];

		foreach ( $all_statuses as $status => $label ) {

			if ( $status === 'deleted' || $the_subscription->can_be_updated_to( $status ) ) {

				if ( in_array( $status, [ 'trash', 'deleted' ], true ) ) {

					if ( current_user_can( 'delete_fsb_subscription', $the_subscription->get_id() ) ) {

						if ( 'trash' === $the_subscription->get_status() ) { // phpcs:ignore
							// TODO: Untrash
							// $actions['untrash'] = '<a title="' . esc_attr( __( 'Restore this item from the Trash', 'flexible-subscriptions' ) ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . '">' . __( 'Restore', 'flexible-subscriptions' ) . '</a>';
						} elseif ( \EMPTY_TRASH_DAYS ) {
							$actions['trash'] = '<a class="submitdelete" title="' . esc_attr( __( 'Move this item to the Trash', 'flexible-subscriptions' ) ) . '" href="' . get_delete_post_link( $the_subscription->get_id() ) . '">' . __( 'Trash', 'flexible-subscriptions' ) . '</a>';
						}

						if ( 'trash' === $the_subscription->get_status() || ! \EMPTY_TRASH_DAYS ) {
							$actions['delete'] = '<a class="submitdelete" title="' . esc_attr( __( 'Delete this item permanently', 'flexible-subscriptions' ) ) . '" href="' . get_delete_post_link( $the_subscription->get_id(), '', true ) . '">' . __( 'Delete Permanently', 'flexible-subscriptions' ) . '</a>';
						}
					}
				} else {

					if ( 'cancelled' === $status && 'pending-cancel' === $the_subscription->get_status() ) {
						$label = __( 'Cancel Now', 'flexible-subscriptions' );
					}

					$actions[ $status ] = sprintf( '<a href="%s">%s</a>', add_query_arg( 'action', $status, $action_url ), $label );

				}
			}
		}

		if ( 'pending' === $the_subscription->get_status() ) {
			unset( $actions['active'] );
			unset( $actions['trash'] );
		} elseif ( ! in_array( $the_subscription->get_status(), [ 'cancelled', 'pending-cancel', 'expired', 'switched', 'suspended' ], true ) ) {
			unset( $actions['trash'] );
		}

		// in HPOS this has to be built manually
		// $column_content .= $wp_list_table->row_actions( $actions );
		return $column_content;
	}

	private function get_delete_url( Subscription $subscription, bool $force_delete ): string {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return add_query_arg();
		}
		return get_delete_post_link( $subscription->get_id(), '', $force_delete );
	}

	private function column_order_title( Subscription $the_subscription ): string {
		return $this->renderer->render( 'subscription/table/order-title', [ 'the_subscription' => $the_subscription ] );
	}

	private function column_recurring_total( Subscription $the_subscription ): string {
		$column_content  = esc_html( wp_strip_all_tags( (string) PriceFormat::subscription( $the_subscription ) ) );
		$column_content .= '<small class="meta">';
		// translators: placeholder is the display name of a payment gateway a subscription was paid by.
		$column_content .= esc_html( sprintf( __( 'Via %s', 'flexible-subscriptions' ), $the_subscription->get_payment_method_to_display() ) );

		$column_content .= '</small>';
		return $column_content;
	}

	private function column_order_items( Subscription $the_subscription ): string {
		$subscription_items = $the_subscription->get_items();
		if ( count( $subscription_items ) === 0 ) {
			return '&ndash;';
		}
			return $this->renderer->render(
				'subscription/table/subscription-items',
				[
					'subscription_items' => $subscription_items,
					'the_subscription'   => $the_subscription,
				]
			);
	}
}
