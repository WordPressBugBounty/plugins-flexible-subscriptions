<?php

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WC_Payment_Gateway;
use WPDesk\FlexibleSubscriptions\Utils\AdminSettingsArray;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\HookProvider\Gateways\WooPaymentsIncompatibility;

/**
 *  Add available gateways for subscriptions column in WooCommerce gateways settings table.
 */
class SupportedGateways implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_payment_gateways_setting_columns', [ $this, 'add_supports_subscriptions_column_header' ] );
		add_filter( 'woocommerce_payment_gateways_setting_column_renewals', [ $this , 'add_supports_subscriptions_column' ] );
		add_filter( 'woocommerce_payment_gateways_settings', [ $this , 'show_unsupported_gateways_information' ] );
	}

	public function add_supports_subscriptions_column_header( array $header ): array {
		$a = new AdminSettingsArray( $header );
		$a->header_insert_after( 'description', [ 'renewals' => __( 'Automatic Renewals', 'flexible-subscriptions' ) ] );

		return $a->getArrayCopy();
	}

	/**
	 * @param WC_Payment_Gateway $gateway
	 */
	public function add_supports_subscriptions_column( $gateway ) {
		echo '<td class="renewals">';

		if ( $gateway->id === WooPaymentsIncompatibility::WOOCOMMERCE_PAYMENTS_ID ) {
			// routed to special handler
			return;
		}

		if ( $gateway->supports( 'subscriptions' ) ) {
			echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Supports Flexible Subscriptions', 'flexible-subscriptions' ) . '">' . esc_html__( 'Yes', 'flexible-subscriptions' ) . '</span>';
		}

		echo '</td>';
	}

	public function show_unsupported_gateways_information( array $settings ): array {
		if ( $this->gateways_support_subscriptions() ) {
			return $settings;
		}

		$settings[] = [
			'type' => 'title',
			'id'   => 'flexible_subscriptions',
			'desc' => __( 'Flexible Subscriptions does not support any payment method available in your store except manual payments. <a target="_blank" href=“https://wpdesk.net/sk/flexible-subscriptions-supported-methods”>See which methods we support →</a>', 'flexible-subscriptions' ),
		];

		return $settings;
	}

	private function gateways_support_subscriptions(): bool {
		$gateways = WC()->payment_gateways()->payment_gateways();

		foreach ( $gateways as $gateway ) {
			if ( $gateway->supports( 'subscriptions' ) ) {
				return true;
			}
		}

		return false;
	}
}
