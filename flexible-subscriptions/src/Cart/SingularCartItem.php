<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

/**
 * Value object representing WooCommerce array cart item.
 */
class SingularCartItem {

	/** @var string */
	private $key;

	/** @var int */
	private $product_id;

	/** @var int */
	private $variation_id = 0;

	/** @var array */
	private $variation = [];

	/** @var int|float */
	private $quantity;

	/** @var string */
	private $data_hash;

	/** @var array */
	private $line_tax_data;

	/** @var float */
	private $line_subtotal;

	/** @var float */
	private $line_subtotal_tax;

	/** @var float */
	private $line_total;

	/** @var float */
	private $line_tax;

	/**
	 * Store any additional cart item data provided by integrations so it can be restored later.
	 *
	 * @var array<string,mixed>
	 */
	private array $extra;

	/**
	 * @param float|int $quantity WC can pass quantity as float.
	 * @param array<int,mixed> $line_tax_data
	 * @param array<int,mixed> $variation
	 */
	public function __construct(
		string $key,
		int $product_id,
		$quantity,
		string $data_hash,
		int $variation_id = 0,
		array $variation = [],
		array $line_tax_data = [],
		float $line_subtotal = 0,
		float $line_subtotal_tax = 0,
		float $line_total = 0,
		float $line_tax = 0,
		array $extra = []
	) {
		$this->key               = $key;
		$this->product_id        = $product_id;
		$this->quantity          = $quantity;
		$this->variation_id      = $variation_id;
		$this->variation         = $variation;
		$this->data_hash         = $data_hash;
		$this->line_tax_data     = $line_tax_data;
		$this->line_subtotal     = $line_subtotal;
		$this->line_subtotal_tax = $line_subtotal_tax;
		$this->line_total        = $line_total;
		$this->line_tax          = $line_tax;
		$this->extra             = $extra;
	}

	/**
	 * @param array<string,mixed> $cart_item
	 */
	public static function from_array( array $cart_item ): self {
		$known_keys = [
			'key',
			'product_id',
			'variation_id',
			'variation',
			'quantity',
			'data_hash',
			'line_tax_data',
			'line_subtotal',
			'line_subtotal_tax',
			'line_total',
			'line_tax',
		];

		$extra = array_diff_key( $cart_item, array_flip( $known_keys ) );

		return new self(
			$cart_item['key'],
			$cart_item['product_id'],
			$cart_item['quantity'],
			$cart_item['data_hash'],
			$cart_item['variation_id'],
			$cart_item['variation'],
			$cart_item['line_tax_data'],
			$cart_item['line_subtotal'],
			$cart_item['line_subtotal_tax'],
			$cart_item['line_total'],
			$cart_item['line_tax'],
			$extra
		);
	}

	public function to_array(): array {
		$cart_item = [
			'key'               => $this->key,
			'product_id'        => $this->product_id,
			'variation_id'      => $this->variation_id,
			'variation'         => $this->variation,
			'quantity'          => $this->quantity,
			'data_hash'         => $this->data_hash,
			'line_tax_data'     => $this->line_tax_data,
			'line_subtotal'     => $this->line_subtotal,
			'line_subtotal_tax' => $this->line_subtotal_tax,
			'line_total'        => $this->line_total,
			'line_tax'          => $this->line_tax,
			'data'              => $this->get_product()->get_original_product(),
		];

		return $cart_item + $this->extra;
	}

	public function get_product(): SubscriptionProductWrapper {
		$product = $this->variation_id > 0 ? wc_get_product( $this->variation_id ) : wc_get_product( $this->product_id );
		if ( $product instanceof SubscriptionProduct ) {
			return new SubscriptionProductWrapper( $product );
		}

		throw new \RuntimeException( 'Invalid product' );
	}
}
