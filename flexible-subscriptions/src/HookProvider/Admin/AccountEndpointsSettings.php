<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Form\Fields\AccountEndpointFields;
use WPDesk\FlexibleSubscriptions\Form\WooCommerceFieldNormalizer;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Utils\AdminSettingsArray;

class AccountEndpointsSettings implements HookProvider {

	private WooCommerceFieldNormalizer $normalizer;

	public function __construct( WooCommerceFieldNormalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_get_settings_advanced', $this );
	}

	/**
	 * @param array<string, mixed[]> $settings
	 *
	 * @return array<string, mixed[]>
	 */
	public function __invoke( $settings ) {
		$a = new AdminSettingsArray( $settings );
		$a->insert_settings_after(
			'woocommerce_myaccount_view_order_endpoint',
			$this->normalizer->normalize( new AccountEndpointFields() )
		);
		return $a->getArrayCopy();
	}
}
