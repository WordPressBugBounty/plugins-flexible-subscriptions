<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

class RecurringLineItem {

	private string $key;

	private int $product_id;

	private ?int $variation_id;

	private array $variation;

	private float $quantity;

	private float $price;

	private string $data_hash;

	/** @var array<int, mixed> */
	private array $line_tax_data;

	private float $line_subtotal;

	private float $line_subtotal_tax;

	private float $line_total;

	private float $line_tax = 0.0;

	/** @var array<string, mixed> */
	private array $cart_item = [];

	/** @var array<string, mixed> */
	private array $extras;

	/**
	 * @param array<string, mixed> $variation
	 * @param array<string, mixed> $cart_item
	 * @param array<int, mixed>    $line_tax_data
	 */
	public function __construct(
		int $product_id,
		float $quantity,
		?int $variation_id = null,
		array $variation = [],
		float $price = 0.0,
		array $cart_item = [],
		array $extras = [],
		string $key = '',
		string $data_hash = '',
		array $line_tax_data = [],
		float $line_subtotal = 0.0,
		float $line_subtotal_tax = 0.0,
		float $line_total = 0.0,
		float $line_tax = 0.0
	) {
		$this->product_id        = $product_id;
		$this->quantity          = $quantity;
		$this->variation_id      = $variation_id;
		$this->variation         = $variation;
		$this->price             = $price;
		$this->cart_item         = $cart_item;
		$this->extras            = $extras;
		$this->key               = $key;
		$this->data_hash         = $data_hash;
		$this->line_tax_data     = $line_tax_data;
		$this->line_subtotal     = $line_subtotal;
		$this->line_subtotal_tax = $line_subtotal_tax;
		$this->line_total        = $line_total;
		$this->line_tax          = $line_tax;
	}

	/**
	 * @param array<string, mixed> $cart_item
	 */
	public static function from_cart_item( array $cart_item ): self {
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
			'data',
		];

		$extra = array_diff_key( $cart_item, array_flip( $known_keys ) );

		$product = $cart_item['data'] ?? null;
		$price   = $product instanceof \WC_Product ? (float) $product->get_price( 'edit' ) : $cart_item['line_total'];

		return new self(
			(int) $cart_item['product_id'],
			(float) $cart_item['quantity'],
			! empty( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : null,
			! empty( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : [],
			$price,
			$cart_item,
			$extra,
			(string) ( $cart_item['key'] ?? '' ),
			(string) ( $cart_item['data_hash'] ?? '' ),
			is_array( $cart_item['line_tax_data'] ?? null ) ? $cart_item['line_tax_data'] : [],
			(float) ( $cart_item['line_subtotal'] ?? 0 ),
			(float) ( $cart_item['line_subtotal_tax'] ?? 0 ),
			(float) ( $cart_item['line_total'] ?? 0 ),
			(float) ( $cart_item['line_tax'] ?? 0 )
		);
	}

	public static function from_order_item( \WC_Order_Item_Product $order_item ): self {
		$product = $order_item->get_product();
		$price   = $product instanceof \WC_Product ? (float) $product->get_price( 'edit' ) : 0.0;

		return new self(
			(int) $order_item->get_product_id(),
			(float) $order_item->get_quantity(),
			$order_item->get_variation_id() ?: null,
			$order_item->get_variation_attributes(),
			$price,
			[],
			[],
			(string) $order_item->get_id(),
			'',
			$order_item->get_taxes(),
			(float) $order_item->get_subtotal(),
			(float) $order_item->get_subtotal_tax(),
			(float) $order_item->get_total(),
			(float) $order_item->get_total_tax()
		);
	}

	public function get_product_id(): int {
		return $this->product_id;
	}

	public function get_variation_id(): ?int {
		return $this->variation_id;
	}

	public function get_quantity(): float {
		return $this->quantity;
	}

	public function get_product(): \WC_Product {
		$product_id = $this->variation_id ?: $this->product_id;

		return wc_get_product( $product_id );
	}

	public function get_subscription_product(): SubscriptionProductWrapper {
		$product = $this->get_product();
		if ( $product instanceof SubscriptionProduct ) {
			return new SubscriptionProductWrapper( $product );
		}

		throw new \RuntimeException( 'Invalid product' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_variation_data(): array {
		return $this->variation;
	}

	public function get_price(): float {
		return $this->price;
	}

	public function get_key(): string {
		return $this->key;
	}

	public function get_data_hash(): string {
		return $this->data_hash;
	}

	/**
	 * @return array<int, mixed>
	 */
	public function get_line_tax_data(): array {
		return $this->line_tax_data;
	}

	public function get_line_subtotal(): float {
		return $this->line_subtotal;
	}

	public function get_line_subtotal_tax(): float {
		return $this->line_subtotal_tax;
	}

	public function get_line_total(): float {
		return $this->line_total;
	}

	public function get_line_tax(): float {
		return $this->line_tax;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_cart_item(): array {
		return $this->cart_item;
	}

	public function to_array(): array {
		$cart_item = [
			'key'               => $this->key,
			'product_id'        => $this->product_id,
			'variation_id'      => $this->variation_id ?? 0,
			'variation'         => $this->variation,
			'quantity'          => $this->quantity,
			'data_hash'         => $this->data_hash,
			'line_tax_data'     => $this->line_tax_data,
			'line_subtotal'     => $this->line_subtotal,
			'line_subtotal_tax' => $this->line_subtotal_tax,
			'line_total'        => $this->line_total,
			'line_tax'          => $this->line_tax,
			'data'              => $this->get_subscription_product()->get_original_product(),
		];

		return $cart_item + $this->extras;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_cart_item_data(): array {
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
			'data',
		];

		if ( empty( $this->cart_item ) ) {
			return $this->extras;
		}

		$extra = array_diff_key( $this->cart_item, array_flip( $known_keys ) );

		return $extra + $this->extras;
	}
}
