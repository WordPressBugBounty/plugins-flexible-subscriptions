<?php

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Utils\AdminSettingsArray;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class AccountSettings implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_account_settings', $this, 1 );
	}

	public function __invoke( array $settings ): array {
		$a = new AdminSettingsArray( $settings );
		$a->insert_settings_after(
			'woocommerce_enable_signup_and_login_from_checkout',
			[
				[
					'desc'          => __( 'When cart contains subscription products', 'flexible-subscriptions' ),
					'desc_tip'      => __( 'Account creation is required for the Flexible Subscriptions to work correctly', 'flexible-subscriptions' ),
					'id'            => 'woocommerce_force_registration_for_subscriptions',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'disabled'      => 'disabled',
					'checkboxgroup' => '',
					'value'         => 'yes',
				],
			]
		);

		return $a->getArrayCopy();
	}
}
