<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Account;

use WPDesk\FlexibleSubscriptions\Settings\Settings;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class AccountEndpoints implements HookProvider {

	/** @var array<string, string> **/
	private array $query_vars;

	private SubscriptionFinder $finder;

	public function __construct( SubscriptionFinder $finder, Settings $settings ) {
		$this->query_vars = [
			'view-fsb-subscription'           => $settings->get( 'woocommerce_myaccount_fsb_view_subscription_endpoint', 'view-subscription' ),
			'fsb-subscriptions'               => $settings->get( 'woocommerce_myaccount_fsb_subscriptions_endpoint', 'subscriptions' ),
			// 'fsb-subscription-payment-method' => $settings->get( 'woocommerce_myaccount_fsb_subscription_payment_method_endpoint', 'subscription-payment-method' ),
		];
		$this->finder = $finder;
	}

	public function hooks(): void {
		add_action( 'init', [ $this, 'add_endpoints' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ], 0 );
		add_action( 'parse_request', [ $this, 'parse_request' ], 0 );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ] );
		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_wc_query_vars' ] );
		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'maybe_redirect_to_only_subscription' ], 10, 2 );
	}

	/**
	 * @param string[] $vars
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		array_push( $vars, ...array_keys( $this->query_vars ) );
		return $vars;
	}

	/**
	 * @param string[] $menu_items
	 * @return string[]
	 */
	public function add_menu_items( $menu_items ) {
		if ( empty( $this->query_vars['fsb-subscriptions'] ) ) {
			return $menu_items;
		}

		if ( $this->user_has_only_one_subscription() ) {
			$label = __( 'My Subscription', 'flexible-subscriptions' );
		} else {
			$label = __( 'Subscriptions', 'flexible-subscriptions' );
		}

		// Add our menu item after the Orders tab if it exists, otherwise just add it to the end.
		$index = array_search( 'orders', array_keys( $menu_items ), true );
		if ( $index !== false ) {
			$menu_items = array_merge( array_slice( $menu_items, 0, $index + 1 ), [ $this->query_vars['fsb-subscriptions'] => $label ], array_slice( $menu_items, $index + 1 ) );
		} else {
			$menu_items['fsb-subscriptions'] = $label;
		}

		return $menu_items;
	}

	private function user_has_only_one_subscription(): bool {
		return 1 === count( $this->finder->find_by_customer( get_current_user_id(), 0, 2 ) );
	}

	/**
	 * @param string $url
	 * @param string $endpoint
	 * @return string
	 */
	public function maybe_redirect_to_only_subscription( $url, $endpoint ) {
		if ( $this->query_vars['fsb-subscriptions'] === $endpoint && is_account_page() ) {
			$subscriptions = $this->finder->find_by_customer( get_current_user_id() );
			if ( $this->user_has_only_one_subscription() ) {
				return $subscriptions[0]->get_view_order_url();
			}
		}

		return $url;
	}

	/**
	 * Parse the request and look for query vars - endpoints may not be supported.
	 *
	 * @return void
	 */
	public function parse_request() {
		global $wp;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Map query vars to their keys, or get them if endpoints are not supported.
		foreach ( $this->query_vars as $key => $var ) {
			if ( isset( $_GET[ $var ] ) ) {
				$wp->query_vars[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $var ] ) );
			} elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * @param array<string, string> $query_vars
	 * @return array<string, string>
	 */
	public function add_wc_query_vars( $query_vars ) {
		return array_merge( $query_vars, $this->query_vars );
	}

	/**
	 * Add endpoints for query vars.
	 *
	 * @return void
	 */
	public function add_endpoints() {
		$mask = $this->get_endpoints_mask();

		foreach ( $this->query_vars as $key => $var ) {
			if ( ! empty( $var ) ) {
				add_rewrite_endpoint( $var, $mask );
			}
		}
	}

	private function get_endpoints_mask(): int {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front     = get_option( 'page_on_front' );
			$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
			$checkout_page_id  = get_option( 'woocommerce_checkout_page_id' );

			if ( in_array( $page_on_front, [ $myaccount_page_id, $checkout_page_id ], true ) ) {
				return \EP_ROOT | \EP_PAGES;
			}
		}

		return \EP_PAGES;
	}
}
