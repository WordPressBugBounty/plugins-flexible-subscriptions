<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Coupons;

use WPDesk\FlexibleSubscriptions\Discount\CalculatorFactory;
use WPDesk\FlexibleSubscriptions\Discount\DiscountingItem;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Utils\CartContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Add new coupons types dedicated for subscription products.
 *
 * @phpstan-type CartItem array{
 *    data: \WC_Product,
 *    data_hash: string,
 *    key: string,
 *    line_subtotal: int,
 *    line_subtotal_tax: int,
 *    line_tax: int,
 *    line_tax_data: array{subtotal: array, total: array},
 *    line_total: int,
 *    product_id: int,
 *    quantity: int,
 *    variation: array,
 *    variation_id: int
 * }
 *
 * @phpstan-type CouponItem array{
 *   key: int|string,
 *   object: \WC_Order_Item_Product|CartItem,
 *   price: int,
 *   product: \WC_Product,
 *   quantity: int
 * }
 */
final class SubscriptionCouponTypes implements HookProvider {

	/** @var CalculatorFactory */
	private $calculator_factory;

	private LoggerInterface $logger;

	private CartContext $cart_context;

	/** @var array<string, array<string, int>> */
	private array $coupon_quantities = [];

	public function __construct( CalculatorFactory $calculator_factory, LoggerInterface $logger, CartContext $cart_context ) {
		$this->calculator_factory = $calculator_factory;
		$this->logger             = $logger;
		$this->cart_context       = $cart_context;
	}

	private const DISCOUNT_TYPES = [
		'sign_up_fee',
		'sign_up_fee_percent',
		'recurring_fee',
		'recurring_percent',
	];

	public function hooks(): void {
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'reset_coupon_quantities' ], 5 );
		add_filter( 'woocommerce_coupon_discount_types', [ $this, 'add_discount_types' ] );
		add_filter( 'woocommerce_product_coupon_types', [ $this, 'add_product_discount_types' ] );

		add_filter( 'woocommerce_coupon_get_discount_amount', [ $this, 'get_discount_amount' ], 10, 5 );

		add_filter( 'woocommerce_coupon_is_valid', [ $this, 'validate_subscription_coupon' ], 10, 3 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', [ $this, 'validate_subscription_coupon_for_product' ], 10, 3 );

		add_filter( 'woocommerce_coupon_get_apply_quantity', [ $this, 'override_applied_quantity_for_recurring_carts' ], 10, 3 );
	}

	public function reset_coupon_quantities( \WC_Cart $cart ): void {
		if ( $cart === $this->cart_context->get_real_global_cart() ) {
			$this->coupon_quantities = [];
		}
	}

	/**
	 * @param array<string, string> $types
	 *
	 * @return array<string, string>
	 */
	public function add_discount_types( $types ) {
		return array_merge(
			$types,
			[
				'sign_up_fee'         => __( 'Sign Up Fee Discount', 'flexible-subscriptions' ),
				'sign_up_fee_percent' => __( 'Sign Up Fee % Discount', 'flexible-subscriptions' ),
				'recurring_fee'       => __( 'Recurring Product Discount', 'flexible-subscriptions' ),
				'recurring_percent'   => __( 'Recurring Product % Discount', 'flexible-subscriptions' ),
			]
		);
	}

	/**
	 * @param string[] $types
	 *
	 * @return string[]
	 */
	public function add_product_discount_types( $types ) {
		return array_merge( $types, self::DISCOUNT_TYPES );
	}

	/**
	 * @param float $discount
	 * @param float $discounting_amount
	 * @param CartItem|\WC_Order_Item_Product $item
	 * @param bool $single
	 * @param \WC_Coupon $coupon
	 *
	 * @return float
	 */
	public function get_discount_amount( $discount, $discounting_amount, $item, $single, $coupon ) {
		$product = is_array( $item ) ? $item['data'] : $item->get_product();

		if ( ! $product instanceof SubscriptionProduct ) {
			return $discount;
		}

		if ( $item instanceof \WC_Order_Item_Product ) {
			$discounting_item = new DiscountingItem(
				$item->get_product(),
				$item->get_quantity(),
				$single
			);
		} else {
			$discounting_item = new DiscountingItem(
				$item['data'],
				(int) $item['quantity'],
				$single
			);
		}

		return $this->calculator_factory
			->get_calculator( $coupon )
			->discount_amount( $discounting_amount, $coupon, $discounting_item );
	}

	/**
	 * General coupon validation -- check if it should be considered to apply. Later the check will
	 * be extended for specific products before applying. Here we can throw exception with a message
	 * for end user, why coupon couldn't be used.
	 *
	 * @param bool $valid
	 * @param \WC_Coupon $coupon
	 * @param \WC_Discounts $discount
	 *
	 * @return bool
	 */
	public function validate_subscription_coupon( $valid, $coupon, $discount ) {
		/** @var CouponItem[] $items */
		// $items = array_values( $discount->get_items() );
		return $valid;
	}

	/**
	 * Validates a subscription coupon's use for a given product. Only check if the coupon should be
	 * applied to specific product. Main logic should be in @see validate_subscription_coupon
	 *
	 * @param bool       $is_valid
	 * @param \WC_Product $product
	 * @param \WC_Coupon  $coupon
	 *
	 * @return bool
	 */
	public function validate_subscription_coupon_for_product( $is_valid, $product, $coupon ) {
		if ( $is_valid === false ) {
			return $is_valid;
		}

		$discount_type = $coupon->get_discount_type();

		if ( ! in_array( $discount_type, self::DISCOUNT_TYPES, true ) ) {
			if ( $product instanceof SubscriptionProduct ) {
				return false;
			}

			return $is_valid;
		}

		$this->logger->debug(
			'Trying to apply discount coupon...',
			[
				'coupon_id'      => $coupon->get_id(),
				'discount_type'  => $discount_type,
				'product_id'     => $product->get_id(),
			]
		);

		if ( ! $product instanceof SubscriptionProduct ) {
			$this->logger->debug(
				'Cannot apply discount coupon for non subscription product "{pid}"',
				[
					'pid'           => $product->get_id(),
					'coupon_id'     => $coupon->get_id(),
					'discount_type' => $discount_type,
				]
			);
			return false;
		}

		$subscription_product = new SubscriptionProductWrapper( $product );

		if (
		in_array(
			$discount_type,
			[ 'sign_up_fee', 'sign_up_fee_percent' ],
			true
		) &&
			! ( $subscription_product->get_signup_fee() > 0 )
		) {
			$this->logger->debug(
				'Sign up fee discount coupon cannot be applied to product "{pid}" which has no sign up fee.',
				[
					'pid'           => $product->get_id(),
					'coupon_id'     => $coupon->get_id(),
					'discount_type' => $discount_type,
				]
			);
			return false;
		}

		$this->logger->debug(
			'Applying discount coupon for product "{pid}".',
			[
				'pid'           => $product->get_id(),
				'coupon_id'     => $coupon->get_id(),
				'discount_type' => $discount_type,
			]
		);
		return true;
	}

	/**
	 * Override the quantity to apply limited coupons to recurring cart items.
	 *
	 * Limited coupons can only apply to x number of items. By default that limit applies
	 * to items in each cart instance. Because recurring carts are separate, the limit applies to
	 * each recurring cart leading to the limit really being x * number-of-recurring-carts.
	 *
	 * This function overrides that by ensuring the limit is accounted for across all recurring carts.
	 * Preserve the initial cart allocation and share any unused allowance with eligible recurring
	 * items, including free trials skipped because their initial price is zero.
	 *
	 * @param int       $apply_quantity The item quantity to apply the coupon to.
	 * @param object    $item The stdClass cart item object. @see WC_Discounts::set_items_from_cart() for an example of object properties.
	 * @param \WC_Coupon $coupon The coupon being applied
	 *
	 * @return int The item quantity to apply the coupon to.
	 */
	public function override_applied_quantity_for_recurring_carts( $apply_quantity, $item, $coupon ) {
		if (
			! in_array( $coupon->get_discount_type(), [ 'recurring_fee', 'recurring_percent' ], true ) ||
			! $coupon->get_limit_usage_to_x_items() ||
			! is_array( $item->object )
		) {
			return $apply_quantity;
		}

		$code = $coupon->get_code();
		// Recurring carts use numeric indexes but retain the original key in the cart item.
		$key = $item->object['key'] ?? $item->key;

		if ( WC()->cart === $this->cart_context->get_real_global_cart() ) {
			$this->coupon_quantities[ $code ][ $key ] = max( 0, (int) $apply_quantity );
			return $apply_quantity;
		}

		if ( ! isset( $this->coupon_quantities[ $code ][ $key ] ) ) {
			$remaining = max( 0, (int) $coupon->get_limit_usage_to_x_items() - array_sum( $this->coupon_quantities[ $code ] ?? [] ) );
			$this->coupon_quantities[ $code ][ $key ] = min( (int) $apply_quantity, $remaining );
		}

		return min( $apply_quantity, $this->coupon_quantities[ $code ][ $key ] ?? 0 );
	}
}
