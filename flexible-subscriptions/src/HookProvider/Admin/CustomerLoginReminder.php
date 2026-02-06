<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Add note in settings that allowing guest customers to place an order doesn't actually affect
 * subscription settings.
 *
 * @phpstan-type SettingEntry array{
 * id?: string,
 * desc_tip: string
 * }
 */
final class CustomerLoginReminder implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_payment_gateways_settings', $this, 10, 1 );
		add_filter( 'woocommerce_account_settings', $this, 10, 1 );
	}

	/**
	 * @param SettingEntry[] $settings
	 *
	 * @return SettingEntry[]
	 */
	public function __invoke( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $value ) {
			if ( isset( $value['id'] ) && 'woocommerce_enable_guest_checkout' === $value['id'] ) {
				$value['desc_tip']  = ! empty( $value['desc_tip'] ) ? $value['desc_tip'] . ' ' : '';
				$value['desc_tip'] .= __( 'Note that purchasing a subscription still requires an account.', 'flexible-subscriptions' );
				break;
			}
		}

		return $settings;
	}
}
