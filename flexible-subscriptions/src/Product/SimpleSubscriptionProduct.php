<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

/**
 * There are some common rules about design of this class, which MUST be obeyed.
 *
 * \WC_Product classes mostly follow Active Record pattern, but we don't want to follow this pattern
 * in most places, where business logic is involved. Thus, products which extend \WC_Product lives
 * mostly to integrate with WooCommerce for CRUD operations compatible with WooCommerce product
 * display, save, etc. However, most of the time our application should deal with
 * SubscriptionProductWrapper, which exposes methods that are common for subscription products, and
 * which are loosely related to WooCommerce itself.
 *
 * Besides dependency between our specialized subscription product object and \WC_Product objects,
 * we also need to use a circular reference, because our specialized class usually knows best
 * how to handle some of more complicated display methods.
 *
 * If this class overrides any method from \WC_Product, it probably means, it was necessary to do so,
 * keeping compatibility with WooCommerce, e.g. to display product price, which is always called by
 * get_price_html() in templates.
 */
class SimpleSubscriptionProduct extends \WC_Product_Simple implements SubscriptionProduct {
	use ProductTypeRenameTrait;

	/** @var SubscriptionProductWrapper */
	private $common_product;

	public function __construct( $product = 0 ) {
		// This clearly violates dependency inversion, but it is the only way to encapsulate common
		// product behavior, without a bunch of static calls, even though we need to use a circular reference.
		$this->common_product = new SubscriptionProductWrapper( $this );
		parent::__construct( $product );
	}

	public function get_type(): string {
		return 'fsb-subscription';
	}

	public function is_type( $type ) {
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
