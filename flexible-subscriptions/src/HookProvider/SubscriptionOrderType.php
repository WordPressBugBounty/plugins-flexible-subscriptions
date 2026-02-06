<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class SubscriptionOrderType implements HookProvider {

	public function hooks(): void {
		add_action( 'init', $this, 6 );
		add_filter( 'wc_order_types', [ $this, 'filter_order_types' ], 10, 2 );
		add_action( 'init', [ $this, 'register_post_statuses' ], 9 );
	}

	public function __invoke(): void {
		$not_found_text = '<p>' . __( 'Subscriptions will appear here for you to view and manage once purchased by a customer.', 'flexible-subscriptions' ) . '</p>';

		wc_register_order_type(
			'fsb_subscription',
			[
				// register_post_type() params.
				'labels'                           => [
					'name'               => __( 'Subscriptions', 'flexible-subscriptions' ),
					'singular_name'      => __( 'Subscription', 'flexible-subscriptions' ),
					'add_new'            => _x( 'Add Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'add_new_item'       => _x( 'Add New Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'edit'               => _x( 'Edit', 'custom post type setting', 'flexible-subscriptions' ),
					'edit_item'          => _x( 'Edit Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'new_item'           => _x( 'New Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'view'               => _x( 'View Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'view_item'          => _x( 'View Subscription', 'custom post type setting', 'flexible-subscriptions' ),
					'search_items'       => __( 'Search Subscriptions', 'flexible-subscriptions' ),
					'not_found'          => $not_found_text,
					'not_found_in_trash' => _x( 'No Subscriptions found in trash', 'custom post type setting', 'flexible-subscriptions' ),
					'parent'             => _x( 'Parent Subscriptions', 'custom post type setting', 'flexible-subscriptions' ),
					'menu_name'          => __( 'Subscriptions', 'flexible-subscriptions' ),
				],
				'description'                      => __( 'This is where subscriptions are stored.', 'flexible-subscriptions' ),
				'public'                           => false,
				'show_ui'                          => true,
				'capability_type'                  => 'shop_order',
				'map_meta_cap'                     => true,
				'publicly_queryable'               => false,
				'exclude_from_search'              => true,
				'show_in_menu'                     => 'flexible-subscriptions',
				'hierarchical'                     => false,
				'show_in_nav_menus'                => false,
				'rewrite'                          => false,
				'query_var'                        => false,
				'supports'                         => [ 'title', 'comments', 'custom-fields' ],
				'has_archive'                      => false,

				// wc_register_order_type() params.
				'exclude_from_orders_screen'       => true,
				'add_order_meta_boxes'             => true,
				'exclude_from_order_count'         => true,
				'exclude_from_order_views'         => true,
				'exclude_from_order_webhooks'      => true,
				'exclude_from_order_reports'       => true,
				'exclude_from_order_sales_reports' => true,
				'class_name'                       => Subscription::class,
			]
		);
	}

	public function register_post_statuses(): void {
		$registered_statuses = get_post_stati();
		$statuses            = array_filter(
			Status::get_statuses(),
			function ( $status ) use ( $registered_statuses ) {
				return ! isset( $registered_statuses[ $status ] );
			},
			\ARRAY_FILTER_USE_KEY
		);

		foreach ( $statuses as $status => $label ) {
				register_post_status(
					$status,
					[
						'label'                     => $label,
						'public'                    => false,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
					]
				);
		}
	}

	/**
	 * To correctly include JS metabox scripts on our pages, we need to convince
	 * WooCommerce that our custom order type is actually one of WooCommerce
	 * order types.
	 *
	 * @param string[] $types
	 * @param string $for
	 *
	 * @return string[]
	 */
	public function filter_order_types( $types, $for ) {
		if ( $for === 'order-meta-boxes' && ! in_array( 'fsb_subscription', $types, true ) ) {
			$types[] = 'fsb_subscription';
		}

		if ( $for === 'admin-menu' && ! in_array( 'fsb_subscription', $types, true ) ) {
			$types[] = 'fsb_subscription';
		}

		return $types;
	}
}
