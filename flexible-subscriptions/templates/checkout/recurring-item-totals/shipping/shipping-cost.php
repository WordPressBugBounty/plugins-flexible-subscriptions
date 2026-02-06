<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 * @var string $shipping_method
 */
defined( 'ABSPATH' ) || exit;

if ( $candidate->display_prices_including_tax() ) {
	$cost = $shipping_method->get_cost() + $shipping_method->get_shipping_tax();
} else {
	$cost = $shipping_method->get_cost();
}
?>

<th>
	<?php esc_html_e( 'Shipping', 'flexible-subscriptions' ); ?>
<br/>
	<small>
		<?php printf( esc_html__( 'Via %s', 'flexible-subscriptions' ), esc_html( $shipping_method->get_label() ) ); ?>
	</small>
</th>

<td>
	<?php echo wp_kses_post( wc_price( $cost ) ); ?>
</td>
