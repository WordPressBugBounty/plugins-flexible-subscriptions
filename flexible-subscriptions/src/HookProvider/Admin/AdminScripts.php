<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\PeriodIterator;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;

class AdminScripts implements \WPDesk\FlexibleSubscriptions\Utils\HookProvider {

	private Plugin $plugin_info;

	public function __construct( Plugin $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', $this );
	}

	public function __invoke(): void {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		wp_register_style(
			'fsb-admin-styles',
			$this->plugin_info->get_url( '/assets/admin/css/admin.css' ),
			[],
			$this->plugin_info->get_version()
		);

		wp_register_script(
			'fsb-product-edit',
			$this->plugin_info->get_url( '/assets/admin/js/product.js' ),
			[ 'jquery', 'wp-i18n' ],
			$this->plugin_info->get_version(),
			true
		);
		wp_localize_script(
			'fsb-product-edit',
			'fsb',
			[ 'frequencyUnits' => iterator_to_array( new PeriodIterator() ) ]
		);

		wp_register_script(
			'subscription-meta-boxes',
			$this->plugin_info->get_url( '/assets/admin/js/subscription.js' ),
			[ 'wc-admin-meta-boxes' ],
			$this->plugin_info->get_version(),
			false
		);
		wp_register_script(
			'subscription-meta-boxes-schedule',
			$this->plugin_info->get_url( '/assets/admin/js/subscription-schedule.js' ),
			[ 'wc-admin-meta-boxes', 'wp-i18n' ],
			$this->plugin_info->get_version(),
			false
		);
		wp_set_script_translations( 'subscription-meta-boxes-schedule', 'flexible-subscriptions' );
		wp_localize_script(
			'subscription-meta-boxes',
			'wcs_admin_meta_boxes',
			[
				'get_customer_orders_nonce'      => wp_create_nonce( 'get-customer-orders' ),
			]
		);

		if (
			$screen->id === 'product' ||
			$screen->id === 'edit-fsb_subscription' ||
			$screen->id === 'woocommerce_page_wc-settings' ||
			$screen->id === wc_get_page_screen_id( 'fsb_subscription' ) ||
			str_contains( $screen->id, 'fsb-settings' )
		) {
			wp_enqueue_style( 'fsb-admin-styles' );
		}

		if ( $screen->id === 'product' ) {
			wp_enqueue_script( 'fsb-product-edit' );
		} elseif ( $screen->id === wc_get_page_screen_id( 'fsb_subscription' ) ) {
			wp_enqueue_script( 'subscription-meta-boxes' );
			wp_enqueue_script( 'subscription-meta-boxes-schedule' );
		}
	}
}
