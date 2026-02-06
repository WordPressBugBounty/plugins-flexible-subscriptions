<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Discount;

/**
 * Simple value object represeting an item being discounted.
 */
final class DiscountingItem {

	/** @var \WC_Product */
	private $product;

	/** @var int */
	private $quantity;

	/** @var bool */
	private $singular;

	public function __construct(
		\WC_Product $product,
		int $quantity,
		bool $singular
	) {
		$this->product  = $product;
		$this->quantity = $quantity;
		$this->singular = $singular;
	}

	public function get_product(): \WC_Product {
		return $this->product;
	}

	public function get_quantity(): int {
		return $this->quantity;
	}

	/** Whether a discount should be applied once, or multiplied by product's quantity. */
	public function is_singular(): bool {
		return $this->singular;
	}
}
