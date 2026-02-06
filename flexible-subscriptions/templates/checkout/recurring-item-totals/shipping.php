<?php
/**
 * Shipping for a subscription candidate wrapped in a HTML table row.
 *
 * Possible scenarios:
 * - Shipping is not required.
 * - No shipping method is available.
 * - Only one shipping method is available.
 * - One shipping method is available but differs by customer location
 * - Multiple shipping methods are available
 *
 * Besides, customer may already have some preferences, which should be exposed by `chosen_shipping_methods`
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 * @var array{'candidate': \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate} $params
 */

defined( 'ABSPATH' ) || exit;

if ( $candidate->is_one_time_payment() || ! $candidate->needs_shipping() ) {
	return;
}

$initial_packages = WC()->shipping()->get_packages();
if ( empty( $initial_packages ) ) {
	$this->output_render( 'checkout/recurring-item-totals/shipping/no-shipping', $params );
	return;
}

$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', [] );

foreach ( $candidate->get_shipping_packages() as $recurring_cart_package_key => $recurring_cart_package ) {
	$package_index = isset( $recurring_cart_package['package_index'] ) ? $recurring_cart_package['package_index'] : 0;
	$package       = WC()->shipping()->calculate_shipping_for_package( $recurring_cart_package );

	if ( count( $package['rates'] ) === 1 ) {
		$shipping_method = array_values( $package['rates'] )[0];
	} elseif ( count( $package['rates'] ) > 1 && isset( $chosen_shipping_methods[ $recurring_cart_package_key ] ) ) {
		$shipping_method = $package['rates'][ $chosen_shipping_methods[ $recurring_cart_package_key ] ];
	} else {
		$this->output_render( 'checkout/recurring-item-totals/shipping/not-selected' );
		return;
	}

	$this->output_render( 'checkout/recurring-item-totals/shipping/shipping-cost', array_merge( $params, [ 'shipping_method' => $shipping_method ] ) );
}
