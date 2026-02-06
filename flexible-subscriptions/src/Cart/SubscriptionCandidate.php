<?php

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

/**
 * An important note about SubscriptionCandidate is that it can be used as sort of a replacement for
 * \WC_Cart class. All method calls are passed to \WC_Cart if not found in this class and our own
 * overrides are handy across the plugin (but not especially suitable for external usage). Think of
 * it as inheritance without extending or simply a decorator.
 *
 * @phpstan-type Package array{
 * 'contents': array{},
 * 'package_index'?: int,
 * 'contents_cost': float,
 * }
 *
 * @method float get_coupon_discount_amount( string $code, bool $ex_tax = true )
 * @method bool display_prices_including_tax()
 * @method string get_displayed_subtotal()
 * @method float get_total_tax()
 * @method float get_shipping_total()
 * @method float get_shipping_tax()
 * @method array get_tax_totals()
 * @method array get_fees()
 * @method array get_coupons()
 * @method float get_taxes_total( bool $compound = true, bool $display = true )
 * @method bool remove_coupon( string $coupon_code )
 * @method void calculate_totals()
 * @property-read float $tax_total
 */
class SubscriptionCandidate implements SubscriptionCandidateInterface {

	/** @var \WC_Cart */
	private $cart;

	/** @var SingularCartItem[] */
	private $contents = [];

	/** @var \DateTimeInterface */
	private $start_date;

	private WPInterval $billing_frequency;

	private ?WPInterval $trial_duration = null;

	private ?WPInterval $expiration = null;

	/**
	 * Cart item key for grouped recurring items.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * We are using internal flag to mark that our object is complete with
	 * whole date related calculations which are performed lazily on
	 * request.
	 *
	 * This is to internalize properties setters and prepare access to
	 * getters only when necessary (during subscription creation).
	 *
	 * @var bool
	 */
	private $initialized = false;

	public function __construct( \WC_Cart $cart, string $group ) {
		// Cleanup cart on construct.
		$this->cart = $cart;
		$this->cart->fees_api()->remove_all_fees();
		$this->cart->cart_contents         = [];
		$this->cart->removed_cart_contents = [];

		$this->group = $group;
	}

	/**
	 * For debug purpose only.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return json_encode(
			[
				'group'             => $this->get_group(),
				'products'          => array_map(
					static function ( SingularCartItem $item ) {
						['product_id' => $product_id, 'quantity' => $quantity, 'total' => $total] = $item->to_array();
						return [
							'product_id' => $product_id,
							'quantity'   => $quantity,
							'total'      => $total,
						];
					},
					$this->contents
				),
				'total'             => $this->get_total(),
				'billing_frequency' => (string) $this->get_billing_frequency(),
				'trial_duration'    => (string) $this->get_trial_duration(),
				'expiration'        => (string) $this->get_expiration(),
			]
		);
	}

	public function add_item( SingularCartItem $item ): void {
		$this->contents[]            = $item;
		$this->cart->cart_contents[] = $item->to_array();
	}

	/** @param SingularCartItem[] $items */
	public function add_items( array $items ): void {
		foreach ( $items as $i ) {
			$this->add_item( $i );
		}
	}

	public function is_empty(): bool {
		return count( $this->contents ) === 0;
	}

	public function get_billing_frequency(): WPInterval {
		if ( ! $this->initialized ) {
			$this->calculate_own_properties();
		}

		return $this->billing_frequency;
	}

	public function get_trial_duration(): ?WPInterval {
		if ( ! $this->initialized ) {
			$this->calculate_own_properties();
		}

		return $this->trial_duration;
	}

	public function has_trial(): bool {
		return $this->get_trial_duration() !== null;
	}

	public function get_trial_end_date(): ?\DateTimeInterface {
		if ( $this->has_trial() ) {
			return $this->get_start_date()->add( $this->get_trial_duration() );
		}

		return null;
	}

	public function get_start_date(): \DateTimeInterface {
		if ( ! $this->initialized ) {
			$this->calculate_own_properties();
		}
		return $this->start_date;
	}

	public function get_first_payment_date(): \DateTimeInterface {
		if ( $this->has_trial() ) {
			return $this->get_start_date()->add( $this->get_trial_duration() );
		}

		return $this->get_start_date()->add( $this->get_billing_frequency() );
	}

	public function get_expiration(): ?WPInterval {
		if ( ! $this->initialized ) {
			$this->calculate_own_properties();
		}
		return $this->expiration;
	}

	/**
	 * Conversely to get_total() method from \WC_Cart, this always returns raw total amount as
	 * float. Not used for HTML display purpose.
	 */
	public function get_total(): float {
		return (float) $this->cart->get_total( 'edit' );
	}

	public function get_subtotal(): float {
		return (float) $this->cart->get_subtotal();
	}

	public function get_displayed_subtotal(): float {
		return (float) $this->cart->get_displayed_subtotal();
	}

	/** @return mixed */
	public function __get( string $name ) {
		if ( property_exists( $this->cart, $name ) ) {
			// @phpstan-ignore property.dynamicName
			return $this->cart->{$name};
		}

		throw new \BadMethodCallException( sprintf( 'Property "%s" does not exist on \WC_Cart object', $name ) );
	}

	/** @return mixed */
	public function __call( string $name, array $arguments ) {
		if ( method_exists( $this->cart, $name ) ) {
			// @phpstan-ignore method.dynamicName
			return $this->cart->{$name}( ...$arguments );
		}

		throw new \BadMethodCallException( sprintf( 'Method "%s" does not exist on \WC_Cart object', $name ) );
	}

	public function get_cart(): \WC_Cart {
		return $this->cart;
	}

	public function needs_payment(): bool {
		return $this->cart->needs_payment();
	}

	/*
	 * It's an overwrite to WC_Cart::needs_shipping which can check if candidate actually needs shipping
	 * with bypass of any hooks.
	 */
	public function needs_shipping(): bool {
		return count( $this->get_shipping_packages() ) > 0;
	}

	/**
	 * @return array<string, Package>
	 */
	public function get_shipping_packages(): array {
		$packages = $this->cart->get_shipping_packages();
		foreach ( $packages as $recurring_cart_package_key => $package ) {
			foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
				$subscription_product = new SubscriptionProductWrapper( $cart_item['data'] );
				if ( $subscription_product->is_one_time_shipping() ) {
					$packages[ $recurring_cart_package_key ]['contents_cost'] -= $cart_item['line_total'];
					unset( $packages[ $recurring_cart_package_key ]['contents'][ $cart_item_key ] );
				}
			}

			if ( empty( $packages[ $recurring_cart_package_key ]['contents'] ) ) {
				unset( $packages[ $recurring_cart_package_key ] );
			}
		}

		return $packages;
	}

	public function get_group(): string {
		return $this->group;
	}

	public function is_one_time_payment(): bool {
		if ( ! $this->initialized ) {
			$this->calculate_own_properties();
		}

		if ( $this->expiration instanceof WPInterval ) {
			return $this->billing_frequency->equalTo( $this->expiration );
		}

		return false;
	}

	private function calculate_own_properties(): void {
		if ( $this->initialized ) {
			return;
		}

		if ( $this->is_empty() ) {
			throw new \RuntimeException( 'Cart is empty' );
		}

		// We only take first product from the current group, as
		// products are grouped by the same date properties so it's
		// should be safe assumption for now.
		[$cart_item] = $this->contents;

		$product = $cart_item->get_product();

		$this->start_date = new \DateTimeImmutable(
			gmdate( 'Y-m-d H:i:s' ),
			new \DateTimeZone( 'UTC' )
		);

		$this->billing_frequency = $product->get_billing_frequency();
		$this->trial_duration    = $product->get_trial_duration();
		$this->expiration        = $product->get_expiration();

		$this->initialized = true;
	}

	public function get_created_via(): string {
		return 'checkout';
	}
}
