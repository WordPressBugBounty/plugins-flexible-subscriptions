<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model;

use WPDesk\FlexibleSubscriptions\Subscription\Model\SubscriptionPlan;

/**
* Keeps everything required to build a single, consistent subscription from cart.
*/
final class SubscriptionProposal {

	private string $key;

	/** @var RecurringLineItem[] */
	private array $items = [];

	private SubscriptionPlan $plan;

	private array $coupons = [];

	/** @var array<string, string> */
	private array $shipping_methods = [];

	public function __construct( string $key, SubscriptionPlan $plan ) {
		$this->key  = $key;
		$this->plan = $plan;
	}

	public function add_item( RecurringLineItem $item ): void {
		$this->items[] = $item;
	}

	public function get_items(): array {
		return $this->items;
	}

	public function is_empty(): bool {
		return empty( $this->items );
	}

	public function get_key(): string {
		return $this->key;
	}

	public function get_plan(): SubscriptionPlan {
		return $this->plan;
	}

	public function add_coupon( string $coupon_code ): void {
		if ( ! in_array( $coupon_code, $this->coupons, true ) ) {
			$this->coupons[] = $coupon_code;
		}
	}

	public function get_coupons(): array {
		return $this->coupons;
	}

	public function set_shipping_method( string $package_key, string $method_id ): void {
		$this->shipping_methods[ $package_key ] = $method_id;
	}

	/**
	 * @param array<string, string> $methods
	 */
	public function set_shipping_methods( array $methods ): void {
		$this->shipping_methods = $methods;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_shipping_methods(): array {
		return $this->shipping_methods;
	}
}
