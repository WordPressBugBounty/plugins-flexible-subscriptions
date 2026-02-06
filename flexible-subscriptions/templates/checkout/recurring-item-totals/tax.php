<?php
/**
 * Taxes for a subscription candidate wrapped in a HTML table row.
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 */

defined( 'ABSPATH' ) || exit;

$itemized_display = 'itemized' === get_option( 'woocommerce_tax_total_display' );

if ( $itemized_display ) {
	$cart_taxes = array_keys( WC()->cart->get_taxes() );
	$taxes      = array_filter(
		$candidate->get_tax_totals(),
		fn ( $recurring_tax ) => in_array( $recurring_tax->tax_rate_id, $cart_taxes, false )
	);
	if ( empty( $taxes ) ) {
		return;
	}
	?>
<th>
	<?php
	foreach ( $taxes as $tax_id => $tax ) {
		echo wp_kses_post( wpautop( $tax->label ) );
	}
	?>
</th>
<td>
	<?php
	foreach ( $taxes as $tax_id => $tax ) {
		echo wp_kses_post( wpautop( $tax->formatted_amount ) );
	}
	?>
</td>
	<?php
} else {
	?>
	<td><?php esc_html_e( 'Tax', 'flexible-subscriptions' ); ?></td>
	<td><?php echo wp_kses_post( wc_price( $candidate->get_total_tax() ) ); ?></td>
	<?php
}
