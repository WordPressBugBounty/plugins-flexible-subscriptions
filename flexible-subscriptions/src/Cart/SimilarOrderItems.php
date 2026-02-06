<?php

namespace WPDesk\FlexibleSubscriptions\Cart;

use IteratorAggregate;
use Traversable;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

final class SimilarOrderItems implements IteratorAggregate {

	use SubscriptionHashCalculatorTrait;

	/** @var array<string, WC_Order_Item_Product[]> */
	private array $items = [];

	public function getIterator(): Traversable {
		return new \ArrayIterator( $this->items );
	}

	public function add( \WC_Order_Item_Product $order_item ) {
		$product = $order_item->get_product();

		if ( ! $product instanceof SubscriptionProduct ) {
			return false;
		}

		$idx = $this->calculate_hash( new SubscriptionProductWrapper( $product ) );

		$this->items[ $idx ][] = $order_item;

		return $idx;
	}

	public function has_items(): bool {
		return ! empty( $this->items );
	}
}
