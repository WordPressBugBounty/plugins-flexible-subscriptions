<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

use WPDesk\FlexibleSubscriptions\Formatting\Price\ProductFullPrice;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

/**
 * This object encapsulates common subscription data shared between simple and variable
 * subscription products (with variations).
 * It heavily relies on product metadata, but strives to provide a consistent way to query that data.
 *
 * @method void set_date_on_sale_from( string|float|int $date )
 * @method void set_date_on_sale_to( string|float|int $date )
 * @method void set_regular_price( string $price )
 * @method bool is_on_sale( string $context = 'view' )
 * @method string get_id()
 * @method string get_price( string $context = 'view' )
 * @method string get_sale_price( string $context = 'view' )
 * @method mixed set_price( string $price )
 * @method mixed set_name( string $name )
 * @method void save()
 */
class SubscriptionProductWrapper {

	/** @var (SubscriptionProduct&\WC_Product) $product */
	private $product;

	/**
	 * @param (SubscriptionProduct&\WC_Product) $product Actually, we cannot typehint it, because subscription product wrapper MUST be compatible with any product. There are cases, when we don't know product type yet, but we need to wrap it.
	 */
	public function __construct( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			throw new \InvalidArgumentException( 'Product must be instance of WC_Product' );
		}

		$this->product = $product;
	}

	/**
	 * Wrapper is kind of a decorator for the product, but we cannot actually decorate it following
	 * the pattern. Nevertheless, it is useful for this object clients to be able to call any method
	 * from the product class, to keep things simple.
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ) {
		if ( method_exists( $this->product, $name ) ) {
			// @phpstan-ignore method.dynamicName
			return $this->product->{$name}( ...$arguments );
		}

		throw new \BadMethodCallException( "Method {$name} does not exist on proxied \WC_Product class." );
	}

	/**
	 * @internal To ensure, we are using an instance of \WC_Product when using external API (like the WC itself), get the original product object. This may save us from a few headaches later.
	 */
	public function get_original_product(): \WC_Product {
		return $this->product;
	}

	public function add_to_cart_text(): string {
		return apply_filters(
			'fsub/product_add_to_cart_text',
			esc_html__( 'Sign up now', 'flexible-subscriptions' ),
			$this->product
		);
	}

	public function is_one_time_shipping(): bool {
		return filter_var( $this->get_meta( '_fsb_subscription_one_time_shipping' ), \FILTER_VALIDATE_BOOLEAN );
	}

	public function get_price_html( array $include ): string {
		return (string) new ProductFullPrice( $this, $include );
	}

	public function get_regular_price(): string {
		return $this->product->get_regular_price( 'edit' );
	}

	/**
	 * Wrapper for \WC_Product::get_meta() always for single value with edit context (without filters).
	 *
	 * @template T
	 *
	 * @param string $key
	 * @param T      $default
	 *
	 * @return mixed|T
	 * @see      \WC_Product::get_meta()
	 */
	public function get_meta( string $key, $default = null ) {
		return $this->product->get_meta( $key, true, 'edit' ) ?: $default;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function add_meta( string $key, $value ): self {
		$this->product->add_meta_data( $key, $value, true );

		return $this;
	}

	public function get_signup_fee() {
		return $this->get_meta( '_fsb_subscription_sign_up_fee', 0 );
	}

	public function set_singup_fee( $fee ): void {
		$this->add_meta( '_fsb_subscription_sign_up_fee', $fee );
	}

	/** @return string Defaults to month */
	public function get_payment_period(): string {
		return $this->get_meta( '_fsb_subscription_period', 'M' );
	}

	public function set_payment_period( string $period ): void {
		$this->add_meta( '_fsb_subscription_period', $period );
	}

	public function get_payment_interval(): int {
		return (int) $this->get_meta( '_fsb_subscription_interval' );
	}

	public function set_payment_interval( int $interval ): void {
		$this->add_meta( '_fsb_subscription_interval', $interval );
	}

	/** @return positive-int|null */
	public function get_total_billing_cycles() {
		return $this->get_meta( '_fsb_total_billing_cycles' );
	}

	public function set_total_billing_cycles( $cycles ): void {
		$this->add_meta( '_fsb_total_billing_cycles', $cycles );
	}

	public function get_length(): int {
		return (int) $this->get_meta( '_fsb_subscription_length' );
	}

	public function set_length( int $length ): void {
		$this->add_meta( '_fsb_subscription_length', $length );
	}

	/** @return int<0, max> */
	public function get_trial_length(): int {
		return (int) $this->get_meta( '_fsb_subscription_trial_length' ) ?: 0;
	}

	/** @phpstan-assert-if-true !null $this->get_trial_duration() */
	public function has_trial(): bool {
		return $this->get_trial_length() > 0;
	}

	public function set_trial_length( $length ): void {
		$this->add_meta( '_fsb_subscription_trial_length', $length );
	}

	public function get_trial_period(): ?string {
		return $this->get_meta( '_fsb_subscription_trial_period' );
	}

	public function set_trial_period( string $period ): void {
		$this->add_meta( '_fsb_subscription_trial_period', $period );
	}

	public function get_billing_frequency(): WPInterval {
		return WPInterval::from_spec( new SingleUnitSpec( $this->get_payment_interval(), $this->get_payment_period() ) );
	}

	public function get_trial_duration(): ?WPInterval {
		if ( $this->has_trial() ) {
			return WPInterval::from_spec( new SingleUnitSpec( $this->get_trial_length(), $this->get_trial_period() ) );
		}

		return null;
	}

	/** @phpstan-assert-if-true !null $this->get_expiration() */
	public function can_expire(): bool {
		return $this->get_total_billing_cycles() > 0;
	}

	public function get_expiration(): ?WPInterval {
		if ( $this->can_expire() ) {
			return WPInterval::from_spec( new SingleUnitSpec( $this->get_payment_interval() * $this->get_total_billing_cycles(), $this->get_payment_period() ) );
		}

		return null;
	}

	/**
	 * Sometimes, a subscription product may be set up in such way, that it isn't actually
	 * recurring, i.e. subscription expiration is set to the same value interval as billing frequency. This
	 * is fine, as subscription products introduce additional features, which may be usable.
	 */
	public function is_recurring(): bool {
		if ( ! $this->can_expire() ) {
			return true;
		}

		return $this->get_billing_frequency()->length() !== $this->get_expiration()->length();
	}
}
