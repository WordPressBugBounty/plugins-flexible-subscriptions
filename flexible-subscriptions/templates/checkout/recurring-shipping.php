<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 * @var int $i
 */

defined( 'ABSPATH' ) || exit;

$packages              = WC()->shipping()->get_packages();
$package               = array_values( $packages )[0];
$available_methods     = $package['rates'];
		$product_names = [];

if ( count( $packages ) > 1 ) {
	foreach ( $package['contents'] as $item_id => $values ) {
		$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
	}
	$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
}
$show_package_details    = count( $packages ) > 1;
$package_details         = implode( ', ', $product_names );
$package_name            = apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'flexible-subscriptions' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'flexible-subscriptions' ), $i, $package );
$index                   = $i;
$formatted_destination   = WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping = WC()->customer->has_calculated_shipping();

$formatted_destination   = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping = ! empty( $has_calculated_shipping );
$calculator_text         = '';

if ( empty( $available_methods ) ) {
	return;
}
?>

<tr class="">
	<th><?php printf( esc_html__( 'Shipment every %s', 'flexible-subscriptions' ), esc_html( $candidate->get_billing_frequency()->to_readable_string() ) ); ?></th>
	<td data-title="<?php echo esc_attr( $package_name ); ?>">
		<?php if ( $available_methods ) : ?>
			<ul id="shipping_method" class="woocommerce-shipping-methods">
				<?php foreach ( $available_methods as $method ) : ?>
					<li>
						<?php
						if ( 1 < count( $available_methods ) ) {
							printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', esc_attr( $index ), esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) );
						} else {
							printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', esc_attr( $index ), esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) );
						}
						printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', esc_attr( $index ), esc_attr( sanitize_title( $method->id ) ), wp_kses( wc_cart_totals_shipping_method_label( $method ), [ 'small' => [ 'class' => '' ] ] ) );
						do_action( 'woocommerce_after_shipping_rate', $method, $index );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>
	</td>
</tr>
