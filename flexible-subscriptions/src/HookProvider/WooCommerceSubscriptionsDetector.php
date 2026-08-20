<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WPDesk\FlexibleSubscriptions\Utils\ExternalPlugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\Notice;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootGate;

/**
 * As Flexible Subscriptions is fatally incompatible with WooCommerce Subscriptions
 * (can't use the same payment gateways simultaneously), we need to check if the other plugin is
 * currently active.
 *
 * @note At the moment, we are able to detect whether WooCommerce Subscriptions is **active**, but
 * not if it's **activating**. An attempt to activate WooCommerce Subscriptions, when Flexible
 * Subscriptions is active will lead to the fatal error (reported in WooCommerce Subscription).
 */
class WooCommerceSubscriptionsDetector implements BootGate {

	public function can_boot(): bool {
		$plugin = new ExternalPlugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
		return $plugin->is_active() === false;
	}

	public function on_failure(): void {
		add_action( 'admin_init', [ $this, 'show_notice' ] );
	}

	public function show_notice(): void {
		$plugin = new ExternalPlugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
		$message = esc_html__( 'We have detected that you currently use WooCommerce Subscriptions. Unfortunately, you cannot use Flexible Subscriptions along the other plugin. Flexible Subscriptions will not be active, until you deactivate WooCommerce Subscriptions.', 'flexible-subscriptions' );

		if ( current_user_can( 'deactivate_plugin', $plugin->get_file_name() ) ) {
			// todo: doesn't work
			global $s;
			$message .= sprintf(
				'<br /><a href="%s">%s</a>',
				wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . urlencode( $plugin->get_file_name() ) . '&amp;plugin_status=all&amp;paged=1&amp;s=' . $s, 'deactivate-plugin_' . $plugin->get_file_name() ),
				esc_html__( 'Deactivate WooCommerce Subscriptions', 'flexible-subscriptions' )
			);
		}

		new Notice(
			$message,
			'error'
		);
	}
}
