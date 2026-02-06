<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class VariationProductAid implements HookProvider {

	public function hooks(): void {
		add_filter(
			'woocommerce_available_variation',
			[ $this, 'maybe_set_variations_price_html' ],
			10,
			3
		);
	}

	/**
	 * @param array<string, mixed> $variation_details
	 * @param \WC_Product $variable_product
	 * @param \WC_Product_Variation $variation
	 *
	 * @return array<string, mixed>
	 */
	public function maybe_set_variations_price_html( $variation_details, $variable_product, $variation ) {
		if ( $variable_product->is_type( 'fsb-variable-subscription' ) && empty( $variation_details['price_html'] ) ) {
			$variation_details['price_html'] = '<span class="price">' . $variation->get_price_html() . '</span>';
		}

		return $variation_details;
	}
}
