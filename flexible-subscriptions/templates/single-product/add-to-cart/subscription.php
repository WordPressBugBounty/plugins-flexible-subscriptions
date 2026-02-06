<?php
/**
 * Subscription Product Add to Cart
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Bail if the product isn't purchasable and that's not because it's limited.
if ( ! $product->is_purchasable() && ! is_user_logged_in() ) {
	return;
}

$user_id = get_current_user_id();

echo wp_kses_post( wc_get_stock_html( $product ) );

if ( $product->is_in_stock() ) { ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<?php
	if ( ! $product->is_purchasable() && 0 !== $user_id ) {
		?>
			<p class="limited-subscription-notice notice"><?php esc_html_e( 'You have an active subscription to this product already.', 'flexible-subscriptions' ); ?></p>
	<?php } else { ?>
		<form class="cart"
				action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
				method="post" enctype='multipart/form-data'>

			<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

			<?php
			do_action( 'woocommerce_before_add_to_cart_quantity' );

			woocommerce_quantity_input(
				[
					'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
					'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
					'input_value' => $product->get_min_purchase_quantity(),
				]
			);

			do_action( 'woocommerce_after_add_to_cart_quantity' );
			?>

			<button type="submit" class="single_add_to_cart_button button alt" name="add-to-cart"
					value="<?php echo esc_attr( $product->get_id() ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

		</form>
	<?php } ?>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php } ?>
