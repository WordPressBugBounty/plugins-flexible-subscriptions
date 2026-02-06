<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Gateways;

use WPDesk\FlexibleSubscriptions\Compatibility\CompatibilityLoader;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * We need to provide the compatibility for existing gateways which
 * support other plugins and this operation usually requires loading
 * some compatibility layer (adapters) of our own, which mimics the
 * logic of foreign plugin.
 */
final class GatewayCompatibilityLoader implements HookProvider {

	/** @var CompatibilityLoader */
	private $loader;

	private bool $can_load = true;

	public function __construct( CompatibilityLoader $loader ) {
		$this->loader = $loader;
	}

	public function hooks(): void {
		add_action( 'init', [ $this, 'disable_loader_on_wcs_activation' ], -100 );
		add_action( 'plugins_loaded', $this, -9 );
	}

	/**
	 * @param \WC_Payment_Gateway[] $gateways
	 *
	 * @return \WC_Payment_Gateway[]
	 */
	public function __invoke() {
		if ( $this->can_load && ! class_exists( 'WC_Subscriptions' ) ) {
			$this->loader->load( 'woocommerce-subscriptions' );
		}
	}

	public function disable_loader_on_wcs_activation(): void {
		if ( $this->is_activating_plugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
			$this->can_load = false;
		}
	}

	private function is_activating_plugin( string $plugin ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		return // single plugin activation
			( isset( $_GET['action'], $_GET['plugin'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'activate' && sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) === $plugin ) ||
				// multiple plugin activation
				( isset( $_POST['action'], $_POST['checked'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'activate-selected' && in_array( $plugin, wp_unslash( $_POST['checked'] ) ) );
		// phpcs:enable
	}
}
