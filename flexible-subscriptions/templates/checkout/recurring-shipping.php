<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringShippingPackage[] $packages
 */

defined( 'ABSPATH' ) || exit;

?>
<style>
.fsb-shipping-methods {
	list-style: none;
	margin-left: 0;
}

.fsb-shipping-contents {
	margin-top: 0.2em;
	font-weight: normal;
	font-style: italic;
	font-size: 0.8em;
	color: #555;
}
</style>
<?php

foreach ( $candidate->get_package_selections() as $package_data ) {
	if ( $package_data->match_initial_rates() ) {
		continue;
	}
	$package_key     = $package_data->get_package_key();
	$rates           = $package_data->get_rates();
	$package_details = $package_data->get_package_details();

	if ( empty( $rates ) ) {
		continue;
	}
	$selected_method = $package_data->get_selected_method();
	?>

	<tr class="">
		<th>
			<?php printf( esc_html__( 'Shipment every %s', 'flexible-subscriptions' ), esc_html( $candidate->get_billing_frequency()->to_readable_string() ) ); ?>
			<?php if ( $package_details ) : ?>
				<?php echo '<p class="fsb-shipping-contents"><small>' . wp_kses_post( $package_details ) . '</small></p>'; ?>
			<?php endif; ?>
		</th>
		<td data-title="<?php esc_attr_e( 'Recurring shipping', 'flexible-subscriptions' ); ?>">
			<ul id="recurring_shipping_method" class="fsb-shipping-methods">
				<?php foreach ( $rates as $rate ) : ?>
					<li>
						<?php
						if ( 1 < count( $rates ) ) {
							printf(
								'<input type="radio" name="shipping_method[%1$s]" data-index="%1$s" id="recurring_shipping_method_%1$s_%2$s" value="%3$s" class="shipping_method" %4$s />',
								esc_attr( $package_key ),
								esc_attr( sanitize_title( $rate->id ) ),
								esc_attr( $rate->id ),
								checked( $rate->id, $selected_method, false )
							);
						} else {
							printf(
								'<input type="hidden" name="shipping_method[%1$s]" data-index="%1$s" id="recurring_shipping_method_%1$s_%2$s" value="%3$s" class="shipping_method" />',
								esc_attr( $package_key ),
								esc_attr( sanitize_title( $rate->id ) ),
								esc_attr( $rate->id )
							);
						}
						printf(
							'<label for="recurring_shipping_method_%1$s_%2$s">%3$s</label>',
							esc_attr( $package_key ),
							esc_attr( sanitize_title( $rate->id ) ),
							wp_kses( wc_cart_totals_shipping_method_label( $rate ), [ 'small' => [ 'class' => '' ] ] )
						);
						do_action( 'woocommerce_after_shipping_rate', $rate, $package_key );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</td>
	</tr>
	<?php
}
