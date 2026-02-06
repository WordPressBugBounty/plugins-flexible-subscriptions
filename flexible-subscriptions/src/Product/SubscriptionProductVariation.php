<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

/**
 * @see SimpleSubscriptionProduct
 */
class SubscriptionProductVariation extends \WC_Product_Variation implements SubscriptionProduct {
	use ProductTypeRenameTrait;

	/** @var SubscriptionProductWrapper */
	private $common_product;

	public function __construct( $product = 0 ) {
		$this->common_product = new SubscriptionProductWrapper( $this );
		parent::__construct( $product );
	}

	public function get_type(): string {
		return 'fsb_subscription_variation';
	}

	/**
	 * Override is_type to correctly support subscription variations, otherwise product attributes
	 * (which variations are built upon) are treated as plain values, leading to fatal errors
	 * across application.
	 *
	 * @param string|string[] $type
	 *
	 * @return bool
	 */
	public function is_type( $type ): bool {
		if ( 'variation' === $type || ( is_array( $type ) && in_array( 'variation', $type, true ) ) ) {
			return true;
		}

		return parent::is_type( $this->rename_type( $type ) );
	}

	public function get_price_html( $deprecated = '' ) {
		return $this->common_product->get_price_html( [ 'price' => parent::get_price_html( $deprecated ) ] );
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
