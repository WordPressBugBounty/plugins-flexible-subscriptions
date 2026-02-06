<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

/**
 * Domain object representing a subscription renewal order.
 *
 * This wrapper encapsulates the renewal-specific logic and meta data,
 * providing a clean domain interface while hiding WooCommerce implementation details.
 * It acts as an anti-corruption layer between the domain and WC_Order.
 */
final class Renewal {

	public const META_ORDER_TYPE      = '_fsb_order_type';
	public const META_SUBSCRIPTION_ID = '_subscription_renewal';
	public const META_BILLING_CYCLE   = '_fsb_billing_cycle';
	public const META_PERIOD_ADVANCED = '_fsb_renewal_period_advanced';
	public const ORDER_TYPE_VALUE     = 'renewal';

	private \WC_Order $order;

	private function __construct( \WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Reconstitute a Renewal from an existing WC_Order.
	 * Returns null if the order is not a valid renewal.
	 */
	public static function from_order( \WC_Order $order ): ?self {
		if ( $order->get_meta( self::META_ORDER_TYPE, true ) !== self::ORDER_TYPE_VALUE ) {
			return null;
		}

		$subscription_id = (int) $order->get_meta( self::META_SUBSCRIPTION_ID, true );
		if ( $subscription_id <= 0 ) {
			return null;
		}

		return new self( $order );
	}

	/**
	 * Check if a given WC_Order is a renewal order.
	 */
	public static function is_renewal_order( \WC_Order $order ): bool {
		return $order->get_meta( self::META_ORDER_TYPE, true ) === self::ORDER_TYPE_VALUE;
	}

	/**
	 * Escape hatch for WooCommerce integration points.
	 * Prefer domain methods over direct order access.
	 */
	public function get_order(): \WC_Order {
		return $this->order;
	}

	public function get_id(): int {
		return $this->order->get_id();
	}

	public function get_subscription_id(): int {
		return (int) $this->order->get_meta( self::META_SUBSCRIPTION_ID, true );
	}

	public function get_billing_cycle(): int {
		$cycle = (int) $this->order->get_meta( self::META_BILLING_CYCLE, true );
		return $cycle > 0 ? $cycle : 1;
	}

	public function needs_payment(): bool {
		return $this->order->needs_payment();
	}

	public function is_paid(): bool {
		return $this->order->is_paid();
	}

	public function is_cancelled(): bool {
		return $this->order->has_status( 'cancelled' );
	}

	/**
	 * Check if this renewal is in-flight (pending payment, not yet completed or cancelled).
	 */
	public function is_in_flight(): bool {
		if ( $this->is_paid() || $this->is_cancelled() ) {
			return false;
		}

		return $this->needs_payment()
			|| in_array( $this->order->get_status(), [ 'pending', 'failed', 'on-hold' ], true );
	}

	public function is_period_advanced(): bool {
		return (bool) $this->order->get_meta( self::META_PERIOD_ADVANCED, true );
	}

	public function mark_period_advanced(): void {
		$this->order->update_meta_data( self::META_PERIOD_ADVANCED, '1' );
	}

	public function set_billing_cycle( int $billing_cycle ): void {
		$this->order->update_meta_data( self::META_BILLING_CYCLE, (string) $billing_cycle );
	}

	public function get_payment_method(): string {
		return $this->order->get_payment_method();
	}

	public function get_total(): float {
		return (float) $this->order->get_total();
	}

	public function save(): void {
		$this->order->save();
	}
}
