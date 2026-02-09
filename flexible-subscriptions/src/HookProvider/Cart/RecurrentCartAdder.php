<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringShippingPackage;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\RecurringShippingResolver;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\ProposalFactory;
use WPDesk\FlexibleSubscriptions\Utils\CartContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Utils\SessionContext;

class RecurrentCartAdder implements HookProvider {

	private const CALCULATE_INITIAL   = 0;
	private const CALCULATE_RECURRING = 1;

	private SubscriptionCandidatesList $candidates;

	private ProposalFactory $proposals_factory;

	private RecurringShippingResolver $shipping_resolver;

	private CartContext $cart_context;

	private SessionContext $session_context;

	/**
	 * We are in a very weird situtation, when one hooked method has to
	 * serve two different purposes: to count actual payment value (money
	 * required from client right now) and recurring payment value (which
	 * doesn't have to be paid yet in some cases).
	 * This may vary, when we include a free trial in our subscription.
	 * We have to do it inside woocommerce_get_price hook because
	 * WC_Calculate_Totals is incredibly closed for extensions and doing it
	 * *the right way* would leave us with possible BC troubles when
	 * WooCommerce introduces some changes to underlying calculations.
	 *
	 * @var int<0,1>
	 * */
	private int $calculation_flag = self::CALCULATE_INITIAL;

	public function __construct( SubscriptionCandidatesList $candidates, ProposalFactory $proposals_factory, RecurringShippingResolver $shipping_resolver, CartContext $cart_context, SessionContext $session_context ) {
		$this->candidates        = $candidates;
		$this->proposals_factory = $proposals_factory;
		$this->shipping_resolver = $shipping_resolver;
		$this->cart_context      = $cart_context;
		$this->session_context   = $session_context;
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
			// We're inside a recursive hook callback.
			return;
		}

		if ( $cart->is_empty() || ! $this->cart_contains_subscription() ) {
			return;
		}

		// Back up the shipping method. Chances are WC is going to wipe the chosen_shipping_methods data.
		$this->session_context->forge_session();

		$this->calculation_flag = self::CALCULATE_RECURRING;

		foreach ( $this->proposals_factory->from_wc_cart( $cart ) as $proposal ) {
			// Create a clone cart to calculate and store totals for this group of
			// subscriptions. Remember it's a shallow clone!
			$recurring_cart = clone $cart;
			$candidate      = new SubscriptionCandidate( $proposal, $recurring_cart );
			foreach ( $proposal->get_coupons() as $coupon_code ) {
				$candidate->apply_coupon( $coupon_code );
			}

			$this->session_context->ensure_shipping_methods_available();

			$package_selections = $this->shipping_resolver->resolve( $candidate );

			$this->cart_context->temporarily_promote_to_global(
				$candidate->get_cart(),
				function () use ( $candidate, $package_selections ): void {
					// This is a very dirty hack allowing us to write the method once in our wrapper class
					// and call it from the hook.
					// The lock is required because SubscriptionCandidate calls underneath WC_Cart, which
					// needs to be filtered in order to achieve actual removal of shipping packages from
					// cart if that's required (for one-time shipping).
					// Furthermore, we need this hook only here, before actual calculation is done, to not
					// bother ourselves later with possible changes to the cart.
					add_filter(
						'woocommerce_cart_shipping_packages',
						$packages_filter_fn = function ( $packages ) use ( $candidate ) {
							static $lock = false;
							if ( ! $lock ) {
								$lock     = true;
								$packages = $candidate->get_shipping_packages();
								$lock     = false;
							}

							return $packages;
						}
					);
					add_filter(
						'woocommerce_package_rates',
						$rates_filter_fn = function ( $rates, $package ) use ( $candidate ) {
							if ( ! isset( $package['recurring_cart_key'] ) || $package['recurring_cart_key'] !== $candidate->get_group() ) {
								return $rates;
							}

							$package_key = $package['package_key'] ?? '';
							if ( $package_key === '' ) {
								return $rates;
							}
							$rate_keys = array_keys( $rates );

							$previous_methods = WC()->session->get( 'previous_shipping_methods', [] );
							$method_counts    = WC()->session->get( 'shipping_method_counts', [] );

							$previous_methods[ $package_key ] = $rate_keys;
							$method_counts[ $package_key ]    = count( $rate_keys );

							WC()->session->set( 'previous_shipping_methods', $previous_methods );
							WC()->session->set( 'shipping_method_counts', $method_counts );

							return $rates;
						},
						50,
						2
					);
					$this->apply_selected_shipping_methods( $candidate );
					$candidate->set_package_selections( $package_selections );
					$candidate->calculate_totals();
					remove_filter( 'woocommerce_package_rates', $rates_filter_fn, 50 );
					remove_filter( 'woocommerce_cart_shipping_packages', $packages_filter_fn );
				}
			);
			$this->candidates->add_candidate( $candidate );
		}

		$this->calculation_flag = self::CALCULATE_INITIAL;

		// Only calculate the initial order cart shipping if we need to show shipping.
		if ( $cart->show_shipping() ) {
			$cart->calculate_shipping();
		}

		$this->session_context->flush();
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
		}

		return $price + $signup_fee;
	}

	private function apply_selected_shipping_methods( SubscriptionCandidate $candidate ): void {
		$chosen_shipping_methods = $this->session_context->get_shipping_methods();
		if ( empty( $chosen_shipping_methods ) ) {
			return;
		}

		$methods = [];
		foreach ( $candidate->get_shipping_packages() as $package_key => $package ) {
			if ( isset( $chosen_shipping_methods[ $package_key ] ) ) {
				$methods[ $package_key ] = (string) $chosen_shipping_methods[ $package_key ];
				continue;
			}

			$package_index = RecurringShippingPackage::index_from_key( $package_key );
			if ( $package_index !== null && isset( $chosen_shipping_methods[ $package_index ] ) ) {
				$methods[ $package_key ] = (string) $chosen_shipping_methods[ $package_index ];
			}
		}

		if ( ! empty( $methods ) ) {
			$candidate->set_shipping_methods( $methods );
			$current_methods = WC()->session->get( 'chosen_shipping_methods', [] );
			WC()->session->set( 'chosen_shipping_methods', array_merge( $current_methods, $methods ) );
		}
	}
}
