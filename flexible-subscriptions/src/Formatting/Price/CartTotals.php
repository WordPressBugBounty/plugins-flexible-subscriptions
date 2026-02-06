<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Formatting\Price;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class CartTotals implements Format {

	private SubscriptionCandidate $candidate;

	public function __construct( SubscriptionCandidate $candidate ) {
		$this->candidate = $candidate;
	}

	public function __toString(): string {
		$billing                    = $this->candidate->get_billing_frequency();
		$order_total_html           = '<strong>' . $this->candidate->get_total() . '</strong> ';
		$tax_total_html             = '';
		$display_prices_include_tax = $this->candidate->display_prices_including_tax();

		// If prices are tax inclusive, show taxes here.
		if ( wc_tax_enabled() && $display_prices_include_tax ) {
			$tax_string_array = [];
			$cart_taxes       = $this->candidate->get_tax_totals();

			if ( get_option( 'woocommerce_tax_total_display' ) === 'itemized' ) {
				foreach ( $cart_taxes as $tax ) {
					$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
				}
			} elseif ( count( $cart_taxes ) > 0 ) {
				$tax_string_array[] = sprintf( '%s %s', wc_price( $this->candidate->get_taxes_total( true, true ) ), WC()->countries->tax_or_vat() );
			}

			if ( count( $tax_string_array ) > 0 ) {
				// translators: placeholder is price string, denotes tax included in cart/order total.
				$tax_total_html = '<small class="includes_tax"> ' . sprintf( _x( '(includes %s)', 'includes tax', 'flexible-subscriptions' ), implode( ', ', $tax_string_array ) ) . '</small>';
			}
		}

		if ( true /* $this->candidate->is_recurring() */ ) {
			$order_total_html = sprintf(
				// translators: 1$: recurring amount, 2$: subscription period (e.g. "month" or "3 months") (e.g. "$15 / month" or "$15 every 2nd month").
				__( '%1$s every %2$s', 'flexible-subscriptions' ),
				$order_total_html,
				$billing->to_readable_string()
			);
		}

		if ( $this->candidate->get_expiration() ) {
			$order_total_html = sprintf(
				// translators: 1$: subscription string (e.g. "$10 up front then $5 on March 23rd every 3rd year"), 2$: length (e.g. "4 years").
				__( '%1$s for %2$s', 'flexible-subscriptions' ),
				$order_total_html,
				WPInterval::from_spec( new SingleUnitSpec( $this->candidate->get_expiration()->length(), $billing->unit() ) )->to_readable_string()
			);
		}

		return $order_total_html . $tax_total_html;
	}
}
