<?php
/**
 * Subtotal for a subscription candidate wrapped in a HTML table row.
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 */

defined( 'ABSPATH' ) || exit;

$discount = array_reduce(
	$candidate->get_coupons(),
	fn ( $carry, $coupon ) => $carry + $candidate->get_coupon_discount_amount( $coupon->get_code(), ! $candidate->display_prices_including_tax() ),
	0
);

if ( $discount === 0 ) {
	return;
}
?>

<th><?php esc_html_e( 'Discount', 'flexible-subscriptions' ); ?></th>
<td>
	<?php
	echo wp_kses_post( '-' . wc_price( $discount ) );
	?>
</td>
