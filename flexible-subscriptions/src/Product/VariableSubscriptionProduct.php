<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

use WPDesk\FlexibleSubscriptions\Formatting\Price\VariableProductEnhancedPrice;

/**
 * @see SimpleSubscriptionProduct
 */
class VariableSubscriptionProduct extends \WC_Product_Variable implements SubscriptionProduct {
	use ProductTypeRenameTrait;

	/** @var SubscriptionProductWrapper */
	private $common_product;

	public function __construct( $product = 0 ) {
		$this->common_product = new SubscriptionProductWrapper( $this );
		parent::__construct( $product );
	}

	public function get_type(): string {
		return 'fsb-variable-subscription';
	}

	public function is_type( $type ) {
		if ( 'variable' === $type || ( is_array( $type ) && in_array( 'variable', $type, true ) ) ) {
			return true;
		}

		return parent::is_type( $this->rename_type( $type ) );
	}

	public function get_price_html( $price = '' ): string {
		return (string) new VariableProductEnhancedPrice( new SubscriptionProductWrapper( $this->get_cheapest_variation() ), $price );
	}

	/**
	 * Dynamically find the cheapest variation from product variations' prices. This will be used to
	 * display _potential_ price for the variable product on store page.
	 *
	 * @return SubscriptionProductVariation
	 */
	private function get_cheapest_variation(): \WC_Product {
		$prices = array_flip( array_values( $this->get_variation_prices() )[0] );

		if ( empty( $prices ) ) {
			// variations may be saved without prices
			$children = $this->get_children();

			if ( empty( $children ) ) {
				// this really shouldn't happen
				return $this;
			}

			return wc_get_product( $children[0] );
		}

		ksort( $prices );
		$lowest_price_product = current( $prices );
		$product              = wc_get_product( $lowest_price_product );

		if ( ! $product instanceof SubscriptionProductVariation ) {
			// just in case
			return $this;
		}

		return $product;
	}

	public function single_add_to_cart_text(): string {
		return $this->common_product->add_to_cart_text();
	}

	public function add_to_cart_text(): string {
		if ( $this->is_purchasable() && $this->is_in_stock() ) {
			return $this->common_product->add_to_cart_text();
		}

		return parent::add_to_cart_text();
	}
}
