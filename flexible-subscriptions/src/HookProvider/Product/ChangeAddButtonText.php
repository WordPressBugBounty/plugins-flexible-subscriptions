<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Settings\Settings;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class ChangeAddButtonText implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private Settings $settings;

	public function __construct( SubscriptionCandidatesList $candidates, Settings $settings ) {
		$this->candidates = $candidates;
		$this->settings   = $settings;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_order_button_text', $this );
		add_filter( 'fsub/product_add_to_cart_text', [ $this, 'product_add_to_cart_text' ], 10, 2 );
	}

	/**
	 * @param string $button_text
	 *
	 * @return string
	 */
	public function __invoke( $button_text ) {
		if ( count( $this->candidates ) === 0 ) {
			return $button_text;
		}

		return $this->settings->get( 'place_order_label', __( 'Sign up now', 'flexible-subscriptions' ) );
	}

	public function product_add_to_cart_text( $button_text, $product ) {
		if ( $this->settings->has( 'fsb_add_to_cart_text' ) ) {
			return $this->settings->get( 'fsb_add_to_cart_text' );
		}

		return $button_text;
	}
}
