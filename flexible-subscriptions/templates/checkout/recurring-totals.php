<?php
/**
 * Recurring totals.
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList $candidates
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\DefaultDateFormat;

defined( 'ABSPATH' ) || exit;
?>
<style>
.subscription-recurring-total {
}

.recurring-total-header,
.recurring-total-schedule {
	display: flex;
	justify-content: space-between;
}

.recurring-total-header {
	font-weight: 700;
}

.recurring-total-details > summary {
	cursor: pointer;
}

.recurring-totals-table {
}
</style>
<script>
document.querySelector('.shop_table .order-total th').innerText = '<?php esc_html_e( 'Total due today', 'flexible-subscriptions' ); ?>';

document.querySelectorAll('.shop_table .cart-discount').forEach(el => {
	const clone = el.cloneNode(true)
	clone.querySelector('.woocommerce-Price-currencySymbol').remove()
	const value = parseInt(clone.querySelector('.woocommerce-Price-amount.amount').innerText)

	if ( value === 0 ) {
		const link = el.querySelector('.woocommerce-remove-coupon')
		el.querySelector('td').innerHTML = ''
		el.querySelector('td').appendChild(link)
	}
})
</script>
<?php

foreach ( $candidates as $candidate ) {
	?>
	<tr class="subscription-recurring-total">
		<td colspan="2">
			<div class="recurring-total-header">
				<span><?php printf( esc_html__( 'Recurring total every %s', 'flexible-subscriptions' ), esc_html( $candidate->get_billing_frequency()->to_readable_string() ) ); ?></span>
				<span><?php echo wp_kses_post( wc_price( $candidate->get_total() ) ); ?></span>
			</div>
			<div class="recurring-total-schedule">
				<small><?php printf( esc_html__( 'Starting: %s', 'flexible-subscriptions' ), esc_html( new DefaultDateFormat( $candidate->get_first_payment_date() ) ) ); ?></small>
				<?php if ( $candidate->get_expiration() ) { ?>
				<small><?php printf( esc_html__( 'For %s', 'flexible-subscriptions' ), esc_html( $candidate->get_expiration()->to_readable_string() ) ); ?></small>
				<?php } ?>
			</div>
			<?php $this->output_render( 'checkout/recurring-item-details', [ 'candidate' => $candidate ] ); ?>
		</td>
	</tr>
	<?php
}
