<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Gateways;

use WC_Payment_Gateway;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\PermanentDismissibleNotice;

/**
 * WooPayment gateway is a special case, tightly coupled to WooCommerce Subscriptions, thus currently (06/25) impossible to use with Flexible Subscriptions.
 */
final class WooPaymentsIncompatibility implements HookProvider {

	public const WOOCOMMERCE_PAYMENTS_ID = 'woocommerce_payments';
	private const NOTICE_ID              = 'fsb-woopayments-incompatibility';

	public function hooks(): void {
		if ( $this->is_woopayments_active_and_enabled() ) {
			add_action( 'admin_notices', [ $this, 'display_incompatibility_notice' ] );
			add_action( 'woocommerce_payment_gateways_setting_column_renewals', [ $this, 'add_unsupported_status_in_table' ], 10, 1 );
		}
	}

	public function display_incompatibility_notice(): void {
		$headline     = esc_html__( 'Flexible Subscriptions: Important Notice Regarding WooPayments', 'flexible-subscriptions' );
		$message_body = sprintf(
			/* translators: %1$s: WooPayments, %2$s: WooCommerce Subscriptions, %3$s: Opening link tag, %4$s: Closing link tag */
			esc_html__( 'We\'ve detected that %1$s is active. Please be aware that this gateway is not compatible with Flexible Subscriptions. To ensure a smooth checkout process, it will be unavailable when a subscription product is in the cart. You can continue to use it for all other standard products. %2$sSee our list of supported gateways%3$s.', 'flexible-subscriptions' ),
			'<strong>WooPayments</strong>',
			'<a href="' . admin_url( '/admin.php?page=wc-settings&tab=checkout' ) . '">',
			'</a>'
		);

		$message = sprintf(
			'<strong>%1$s.</strong> %2$s',
			$headline,
			$message_body
		);

		new PermanentDismissibleNotice( $message, self::NOTICE_ID, 'warning' );
	}

	/**
	 * @param \WC_Payment_Gateway $gateway
	 */
	public function add_unsupported_status_in_table( $gateway ): void {
		if ( $gateway->id !== self::WOOCOMMERCE_PAYMENTS_ID ) {
			return;
		}

		echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Unsupported by Flexible Subscriptions', 'flexible-subscriptions' ) . '">' . esc_html__( 'Unsupported', 'flexible-subscriptions' ) . '</span>';
	}

	private function is_woopayments_active_and_enabled(): bool {
		if ( ! class_exists( 'WC_Payments' ) ) {
			return false;
		}

		$settings = get_option( 'woocommerce_woocommerce_payments_settings', [] );

		return isset( $settings['enabled'] ) && 'yes' === $settings['enabled'];
	}
}
