<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

final class OrderColumns implements HookProvider {

	private SubscriptionFinder $finder;

	private Renderer $renderer;

	public function __construct( SubscriptionFinder $finder, Renderer $renderer ) {
		$this->finder   = $finder;
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_contains_subscription_column' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'add_contains_subscription_column_content' ], 10, 2 );

		add_filter( 'woocommerce_shop_order_list_table_columns', [ $this, 'add_contains_subscription_column' ] );
		add_action( 'woocommerce_shop_order_list_table_custom_column', [ $this, 'add_contains_subscription_column_content' ], 10, 2 );

		add_action( 'restrict_manage_posts', [ $this, 'add_contains_subscription_filter' ] );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ $this, 'add_contains_subscription_filter' ] );
	}

	public function add_contains_subscription_filter( $post_type ) {
		if ( $post_type !== 'shop_order' ) {
			return;
		}

		$order_types = apply_filters(
			'fsub/admin/order/order_types',
			[
				'regular'     => _x( 'Non-subscription', 'An order type', 'flexible-subscriptions' ),
				'parent'      => _x( 'Subscription Parent', 'An order type', 'flexible-subscriptions' ),
				'renewal'     => _x( 'Subscription Renewal', 'An order type', 'flexible-subscriptions' ),
				'shipping'    => _x( 'Order Shipping', 'An order type', 'flexible-subscriptions' ),
			]
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_shop_order_subtype = isset( $_GET['shop_order_subtype'] ) ? wc_clean( wp_unslash( $_GET['shop_order_subtype'] ) ) : '';

		$this->renderer->output_render(
			'order/order-type-filter',
			[
				'order_types' => $order_types,
				'selected'    => $selected_shop_order_subtype,
			]
		);
	}

	public function add_contains_subscription_column( $columns ) {
		$columns['related_subscription'] = esc_html__( 'Related subscription', 'flexible-subscriptions' );

		return $columns;
	}

	public function add_contains_subscription_column_content( $column_name, $id ): void {
		if ( 'related_subscription' !== $column_name ) {
			return;
		}

		$order = wc_get_order( $id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$parent = $this->finder->find( $order->get_parent_id() );

		if ( $parent instanceof Subscription ) {
			printf(
				'<a href="%s">#%s</a>',
				esc_url( $parent->get_edit_order_url() ),
				esc_html( $parent->get_order_number() )
			);
		}

		$created_subscriptions = $this->finder->find_all_by_order( $order );

		$links = [];
		foreach ( $created_subscriptions as $subscription ) {
			$links[] = sprintf(
				'<a href="%s">#%s</a>',
				esc_url( $subscription->get_edit_order_url() ),
				esc_html( $subscription->get_order_number() )
			);
		}
		echo wp_kses_post( implode( ', ', $links ) );
	}
}
