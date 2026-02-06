<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class ApiSubscriptionCandidate implements SubscriptionCandidateInterface {

	private \WC_Order $parent_order;

	private \WC_Order_Item_Product $order_item;

	private array $items;

	private SubscriptionProductWrapper $product_wrapper;

	private \DateTimeImmutable $start_date;

	public function __construct( \WC_Order $parent_order, array $order_items ) {
		if ( empty( $order_items ) ) {
			throw new \InvalidArgumentException( 'Order items list cannot be empty for subscription candidate.' );
		}

		$this->parent_order = $parent_order;
		$this->items        = $order_items;

		$this->order_item      = reset( $order_items );
		$this->product_wrapper = new SubscriptionProductWrapper( $this->order_item->get_product() );

		$this->start_date = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}

	public function get_billing_frequency(): WPInterval {
		return $this->product_wrapper->get_billing_frequency();
	}

	public function get_start_date(): \DateTimeInterface {
		return $this->start_date;
	}

	public function get_order_item(): \WC_Order_Item_Product {
		return $this->order_item;
	}

	public function get_order_items(): array {
		return $this->items;
	}

	public function get_trial_end_date(): ?\DateTimeInterface {
		$duration = $this->product_wrapper->get_trial_duration();

		return $duration ? $this->start_date->add( $duration ) : null;
	}

	public function get_expiration(): ?WPInterval {
		return $this->product_wrapper->get_expiration();
	}

	public function get_created_via(): string {
		return 'rest-api';
	}
}
