<?php
/**
 * @var array{'candidate': \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate} $params
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 */

defined( 'ABSPATH' ) || exit;
?>

<details class="recurring-total-details">
	<summary><small><?php esc_html_e( 'Details', 'flexible-subscriptions' ); ?></small></summary>
	<table class="shop_table recurring-totals-table">
		<tbody>
			<tr class="total">
				<?php $this->output_render( 'checkout/recurring-item-totals/subtotal', $params ); ?>
			</tr>
			<?php if ( ! empty( WC()->cart->get_coupons() ) ) { ?>
			<tr class="discount">
				<?php $this->output_render( 'checkout/recurring-item-totals/discount', $params ); ?>
			</tr>
			<?php } ?>
			<?php if ( $candidate->needs_shipping() && WC()->cart->show_shipping() ) { ?>
			<tr class="shipping">
				<?php $this->output_render( 'checkout/recurring-item-totals/shipping', $params ); ?>
			</tr>
			<?php } ?>
			<?php if ( ! empty( $candidate->get_fees() ) ) { ?>
			<tr class="fees">
				<?php $this->output_render( 'checkout/recurring-item-totals/fee', $params ); ?>
			</tr>
			<?php } ?>
			<?php
			$tax_display_mode = WC()->cart->get_tax_price_display_mode();
			if ( wc_tax_enabled() && 'excl' === $tax_display_mode ) {
				?>
			<tr class="taxes">
				<?php $this->output_render( 'checkout/recurring-item-totals/tax', $params ); ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</details>
