<?php

namespace WPDesk\FlexibleSubscriptions\Cart;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringLineItem;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringShippingPackage;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\SubscriptionProposal;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

/**
 * An important note about SubscriptionCandidate is that it can be used as a sort of replacement for
 * \WC_Cart class. All method calls are passed to \WC_Cart if not found in this class and our own
 * overrides are handy across the plugin (but not especially suitable for external usage). Think of
 * it as inheritance without extending or simply a decorator.
 *
 * @phpstan-type Package array{
 * 'contents': array{},
 * 'contents_cost': float,
 * }
 *
 * @method float get_coupon_discount_amount( string $code, bool $ex_tax = true )
 * @method bool display_prices_including_tax()
 * @method float get_total_tax()
 * @method float get_shipping_total()
 * @method float get_shipping_tax()
 * @method array get_tax_totals()
 * @method array get_fees()
 * @method array get_coupons()
 * @method float get_taxes_total( bool $compound = true, bool $display = true )
 * @method bool remove_coupon( string $coupon_code )
 * @method void calculate_totals()
 * @method void apply_coupon( string $coupon_code )
 *
 * @property-read float $tax_total
 */
class SubscriptionCandidate implements SubscriptionCandidateInterface {

	private \WC_Cart $cart;

	private ?\DateTimeInterface $start_date = null;

	private SubscriptionProposal $proposal;

	/** @var RecurringShippingPackage[] */
	private array $package_selections = [];

	public function __construct( SubscriptionProposal $proposal, \WC_Cart $cart ) {
		// Cleanup cart on construct.
		$this->cart = $cart;
		$this->cart->empty_cart( false );

		$this->proposal = $proposal;
		// todo: unclean, but we need to populate cart contents for totals calculation
		$this->add_items( $proposal->get_items() );
	}

	/**
	 * For debug purpose only.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return json_encode(
			[
				'group'             => $this->proposal->get_key(),
				'products'          => array_map(
					static function ( RecurringLineItem $item ) {
						return [
							'product_id' => $item->get_product_id(),
							'quantity'   => $item->get_quantity(),
							'total'      => $item->get_line_total(),
						];
					},
					$this->proposal->get_items()
				),
				'total'             => $this->get_total(),
				'billing_frequency' => (string) $this->get_billing_frequency(),
				'trial_duration'    => (string) $this->get_trial_duration(),
				'expiration'        => (string) $this->get_expiration(),
			]
		);
	}

	public function add_item( RecurringLineItem $item ): void {
		$this->cart->cart_contents[] = $item->to_array();
	}

	/** @param RecurringLineItem[] $items */
	public function add_items( array $items ): void {
		foreach ( $items as $i ) {
			$this->add_item( $i );
		}
	}

	public function is_empty(): bool {
		return $this->proposal->is_empty();
	}

	public function get_billing_frequency(): WPInterval {
		return $this->proposal->get_plan()->get_billing_frequency();
	}

	public function get_trial_duration(): ?WPInterval {
		return $this->proposal->get_plan()->get_trial_period();
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
		return $this->start_date ??= new \DateTimeImmutable(
			gmdate( 'Y-m-d H:i:s' ),
			new \DateTimeZone( 'UTC' )
		);
	}

	public function get_first_payment_date(): \DateTimeInterface {
		if ( $this->has_trial() ) {
			return $this->get_start_date()->add( $this->get_trial_duration() );
		}

		return $this->get_start_date()->add( $this->get_billing_frequency() );
	}

	public function get_expiration(): ?WPInterval {
		return $this->proposal->get_plan()->get_expiration();
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

	public function get_shipping_package_key( $package_index ): string {
		return 'fsb_' . $this->proposal->get_key() . '_' . $package_index;
	}

	/**
	 * @param array<string, string> $methods
	 */
	public function set_shipping_methods( array $methods ): void {
		$this->proposal->set_shipping_methods( $methods );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_shipping_methods(): array {
		return $this->proposal->get_shipping_methods();
	}

	public function get_shipping_method( string $package_key ): ?string {
		$methods = $this->proposal->get_shipping_methods();

		return $methods[ $package_key ] ?? null;
	}

	/** @param RecurringShippingPackage[] $selections */
	public function set_package_selections( array $selections ): void {
		$this->package_selections = $selections;
	}

	/** @return RecurringShippingPackage[] */
	public function get_package_selections(): array {
		return $this->package_selections;
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

		foreach ( $packages as $key => $package ) {
			// Remap to our custom keys to avoid collision with main WooCommerce packages.
			if ( is_numeric( $key ) ) {
				$package_key                   = $this->get_shipping_package_key( $key );
				$package['recurring_cart_key'] = $this->proposal->get_key();
				$package['package_key']        = $package_key;
				$packages[ $package_key ]      = $package;
				unset( $packages[ $key ] );
			}
		}

		return $packages;
	}

	public function get_group(): string {
		return $this->proposal->get_key();
	}

	public function is_one_time_payment(): bool {
		return $this->proposal->get_plan()->is_one_time();
	}

	public function get_created_via(): string {
		return 'checkout';
	}
}
