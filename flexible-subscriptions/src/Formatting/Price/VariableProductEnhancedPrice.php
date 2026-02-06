<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Formatting\Price;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

/**
 * Display combined price string for variable products, including permeations like "From 10$ every 2 months"
 */
class VariableProductEnhancedPrice implements Format {

	private SubscriptionProductWrapper $product;

	private $extra;

	public function __construct( SubscriptionProductWrapper $product, $extra = [] ) {
		$this->product = $product;
		$this->extra   = $extra;
	}

	public function __toString(): string {
		$billing = $this->product->get_billing_frequency();
		$price   = $this->extra['price'] ?? $this->get_price_based_on_taxes_settings( $this->product->get_price() );

		if ( $this->product->is_recurring() ) {
			$subscription_string = sprintf(
				// translators: 1$: recurring amount, 2$: subscription period (e.g. "month" or "3 months") (e.g. "$15 / month" or "$15 every 2nd month").
				__( 'From %1$s every %2$s', 'flexible-subscriptions' ),
				$price,
				$billing->to_readable_string()
			);
		} else {
			$subscription_string = $price; // Only for one billing period so show "$5 for 3 months" instead of "$5 every 3 months for 3 months".
		}

		if ( $this->product->can_expire() ) {
			$expiration          = $this->product->get_expiration();
			$subscription_string = sprintf(
				// translators: 1$: subscription string (e.g. "$10 up front then $5 on March 23rd every 3rd year"), 2$: length (e.g. "4 years").
				__( '%1$s for %2$s', 'flexible-subscriptions' ),
				$subscription_string,
				WPInterval::from_spec( new SingleUnitSpec( $expiration->length(), $expiration->unit() ) )->to_readable_string()
			);
		}

		if ( $this->product->has_trial() ) {
			$trial        = $this->product->get_trial_duration();
			$trial_string = $trial->to_readable_string();
			// translators: 1$: subscription string (e.g. "$15 on March 15th every 3 years for 6 years"), 2$: trial length (e.g.: "with 4 months free trial").
			$subscription_string = sprintf( __( '%1$s with %2$s free trial', 'flexible-subscriptions' ), $subscription_string, $trial_string );
		}

		if ( $this->product->get_signup_fee() > 0 ) {
			$sign_up_fee = $this->get_price_based_on_taxes_settings( $this->product->get_signup_fee() );
			// translators: 1$: subscription string (e.g. "$15 on March 15th every 3 years for 6 years with 2 months free trial"), 2$: signup fee price (e.g. "and a $30 sign-up fee").
			$subscription_string = sprintf( __( '%1$s and a %2$s sign-up fee', 'flexible-subscriptions' ), $subscription_string, $sign_up_fee );
		}

		return apply_filters(
			'woocommerce_get_price_html',
			$subscription_string,
			$this->product->get_original_product()
		);
	}

	private function get_price_based_on_taxes_settings( $price ) {
		switch ( get_option( 'woocommerce_tax_display_shop' ) ) {
			case 'incl':
				$price = wc_get_price_including_tax( $this->product->get_original_product(), [ 'price' => $price ] );
				break;
			case 'excl':
			case 'exclude_tax':
				$price = wc_get_price_excluding_tax( $this->product->get_original_product(), [ 'price' => $price ] );
				break;
		}

		return wc_price( $price );
	}
}
