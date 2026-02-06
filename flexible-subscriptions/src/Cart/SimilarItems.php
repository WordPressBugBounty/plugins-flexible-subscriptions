<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Cart;

use IteratorAggregate;
use Traversable;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;


/**
 * Allocate similar products with its cart hash to build groups, which can be groupped together in
 * one subscription.
 *
 * @phpstan-type CartItem array{
 *    data: \WC_Product,
 *    data_hash: string,
 *    key: string,
 *    line_subtotal: int,
 *    line_subtotal_tax: int,
 *    line_tax: int,
 *    line_tax_data: array{subtotal: array, total: array},
 *    line_total: int,
 *    product_id: int,
 *    quantity: numeric,
 *    variation: array,
 *    variation_id: int
 * }
 *
 * @implements IteratorAggregate<string,SingularCartItem[]>
 */
final class SimilarItems implements \IteratorAggregate {

	use SubscriptionHashCalculatorTrait;

	/** @var array<string, SingularCartItem[]> */
	private array $items = [];

	public function getIterator(): Traversable {
		return new \ArrayIterator( $this->items );
	}

	/**
	 * @param CartItem $cart_item
	 *
	 * @return string|false
	 */
	public function add( array $cart_item ) {
		if ( ! $cart_item['data'] instanceof SubscriptionProduct ) {
			return false;
		}

		$idx = $this->calculate_hash( new SubscriptionProductWrapper( $cart_item['data'] ) );

		$this->items[ $idx ][] = SingularCartItem::from_array( $cart_item );

		return $cart_item['key'];
	}
}
