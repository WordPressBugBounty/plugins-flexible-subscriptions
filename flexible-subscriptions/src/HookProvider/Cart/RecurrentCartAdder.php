<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Cart\SimilarItems;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class RecurrentCartAdder implements HookProvider {

	private const CALCULATE_INITIAL   = 0;
	private const CALCULATE_RECURRING = 1;

	private SubscriptionCandidatesList $candidates;

	/**
	 * We are in a very weird situtation, when one hooked method has to
	 * serve two different purposes: to count actual payment value (money
	 * required from client right now) and recurring payment value (which
	 * doesn't have to be paid yet in some cases).
	 *
	 * This may vary, when we include a free trial in our subscription.
	 *
	 * We have to do it inside woocommerce_get_price hook because
	 * WC_Calculate_Totals is incredibly closed for extensions and doing it
	 * *the right way* would leave us with possible BC troubles when
	 * WooCommerce introduces some changes to underlying calculations.
	 *
	 * @var int<0,1>
	 * */
	private int $calculation_flag = self::CALCULATE_INITIAL;

	public function __construct( SubscriptionCandidatesList $candidates ) {
		$this->candidates = $candidates;
	}

	public function hooks(): void {
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'add_calculation_price_filter' ], 10 );
		add_action( 'woocommerce_calculate_totals', [ $this, 'remove_calculation_price_filter' ], 10 );
		add_action( 'woocommerce_after_calculate_totals', [ $this, 'remove_calculation_price_filter' ], 10 );

		add_action( 'woocommerce_calculate_totals', [ $this, 'add_recurrent_items_to_cart' ], 10 );

		add_filter( 'woocommerce_add_to_cart_handler', [ $this, 'handle_cart' ], 10, 1 );
	}

	/**
	 *  @param string $handler
	 *
	 *  @return string
	 */
	public function handle_cart( $handler ) {
		switch ( $handler ) {
			case 'fsb-variable-subscription':
				return 'variable';
			case 'fsb-subscription':
				return 'simple';
			default:
				return $handler;
		}
	}

	private function cart_contains_subscription(): bool {
		foreach ( WC()->cart->cart_contents as $cart_item ) {
			if ( $cart_item['data'] instanceof SubscriptionProduct ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \WC_Cart $cart
	 */
	public function add_recurrent_items_to_cart( $cart ): void {
		if ( $this->calculation_flag === self::CALCULATE_RECURRING ) {
			// We're inside recursive hook callback.
			return;
		}

		if ( ! $this->cart_contains_subscription() ) {
			return;
		}

		$subscription_groups = new SimilarItems();

		foreach ( $cart->get_cart() as $cart_item ) {
			$subscription_groups->add( $cart_item );
		}

		$recurring_carts = [];

		// Back up the shipping method. Chances are WC is going to wipe the chosen_shipping_methods data.
		WC()->session->set( 'fsb_shipping_methods', WC()->session->get( 'chosen_shipping_methods', [] ) );

		$this->calculation_flag = self::CALCULATE_RECURRING;

		foreach ( $subscription_groups as $group_key => $items ) {
			// Create a clone cart to calculate and store totals for this group of
			// subscriptions. Remember it's a shallow clone!
			$recurring_cart = clone $cart;
			$candidate      = new SubscriptionCandidate( $recurring_cart, $group_key );
			$candidate->add_items( $items );

			foreach ( $cart->get_applied_coupons() as $coupon_code ) {
				$coupon      = new \WC_Coupon( $coupon_code );
				$coupon_type = $coupon->get_discount_type();

				if ( ! in_array( $coupon_type, [ 'recurring_fee', 'recurring_percent' ], true ) ) {
					$candidate->remove_coupon( $coupon_code );
				}
			}

			// This is a very dirty hack allowing us to write the method once in our wrapper class
			// and call it from the hook.
			// The lock is required because SubscriptionCandidate calls underneath WC_Cart, which
			// needs to be filtered in order to achieve actual removal of shipping packages from
			// cart if that's required (for one-time shipping).
			// Furthermore, we need this hook only here, before actual calculation is done, to not
			// bother ourselves later with possible changes to the cart.
			add_filter(
				'woocommerce_cart_shipping_packages',
				$fn = function ( $packages ) use ( $candidate ) {
					static $lock = false;
					if ( ! $lock ) {
						$lock     = true;
						$packages = $candidate->get_shipping_packages();
						$lock     = false;
					}

					return $packages;
				}
			);

			$candidate->calculate_totals();

			remove_filter( 'woocommerce_cart_shipping_packages', $fn );

			$this->candidates->add_candidate( $candidate );
		}

		$this->calculation_flag = self::CALCULATE_INITIAL;

		// Only calculate the initial order cart shipping if we need to show shipping.
		if ( $cart->show_shipping() ) {
			$cart->calculate_shipping();
		}

		// We no longer need our backup of shipping methods.
		unset( WC()->session->fsb_shipping_methods );
	}

	public function add_calculation_price_filter(): void {
		if ( ! $this->cart_contains_subscription() ) {
			return;
		}

		add_filter( 'woocommerce_product_get_price', [ $this, 'set_subscription_prices_for_calculation' ], 100, 2 );
		add_filter( 'woocommerce_product_variation_get_price', [ $this, 'set_subscription_prices_for_calculation' ], 100, 2 );
	}

	public function remove_calculation_price_filter(): void {
		remove_filter( 'woocommerce_product_get_price', [ $this, 'set_subscription_prices_for_calculation' ], 100 );
		remove_filter( 'woocommerce_product_variation_get_price', [ $this, 'set_subscription_prices_for_calculation' ], 100 );
	}

	/**
	 * @param float $price
	 * @param \WC_Product $product
	 *
	 * @return float
	 */
	public function set_subscription_prices_for_calculation( $price, $product ) {
		if ( ! $product instanceof SubscriptionProduct ) {
			return $price;
		}

		if ( $this->calculation_flag === self::CALCULATE_RECURRING ) {
			return $price;
		}

		$subscription = new SubscriptionProductWrapper( $product );

		$signup_fee = $subscription->get_signup_fee();

		if ( $subscription->has_trial() ) {
			return $signup_fee;
		} else {
			return $price + $signup_fee;
		}
	}
}
