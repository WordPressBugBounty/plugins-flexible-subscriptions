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

$package_selections = $candidate->get_package_selections();

if ( empty( $package_selections ) ) {
	$this->output_render( 'checkout/recurring-item-totals/shipping/no-shipping', $params );
	return;
}

foreach ( $package_selections as $package_data ) {
	$rates = $package_data->get_rates();

	if ( empty( $rates ) ) {
		$this->output_render( 'checkout/recurring-item-totals/shipping/no-shipping', $params );
		return;
	}
	$selected_method = $package_data->get_selected_method();

	if ( count( $rates ) === 1 ) {
		$shipping_method = array_values( $rates )[0];
	} elseif ( count( $rates ) > 1 && $selected_method && isset( $rates[ $selected_method ] ) ) {
		$shipping_method = $rates[ $selected_method ];
	} else {
		$this->output_render( 'checkout/recurring-item-totals/shipping/not-selected' );
		return;
	}

	$this->output_render( 'checkout/recurring-item-totals/shipping/shipping-cost', array_merge( $params, [ 'shipping_method' => $shipping_method ] ) );
}
