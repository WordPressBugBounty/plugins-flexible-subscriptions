<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WPDesk\FlexibleSubscriptions\Utils\ExternalPlugin;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\Notice;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\StoppableBinder;

/**
 * As Flexible Subscriptions is fatally incompatible with WooCommerce Subscriptions
 * (can't use the same payment gateways simultaneously), we need to check if the other plugin is
 * currently active.
 *
 * @note At the moment, we are able to detect whether WooCommerce Subscriptions is **active**, but
 * not if it's **activating**. An attempt to activate WooCommerce Subscriptions, when Flexible
 * Subscriptions is active will lead to the fatal error (reported in WooCommerce Subscription).
 */
class WooCommerceSubscriptionsDetector implements HookProvider, StoppableBinder {

	public function should_stop(): bool {
		$plugin = new ExternalPlugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
		return $plugin->is_active();
	}

	public function hooks(): void {
		add_action( 'admin_init', $this );
	}

	public function __invoke(): void {
		$plugin = new ExternalPlugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
		if ( $plugin->is_active() ) {
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
}
