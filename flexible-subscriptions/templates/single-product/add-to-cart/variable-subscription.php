<?php
/**
 * Variable subscription product add to cart
 *
 * @var array $available_variations
 * @var array<string, string[]> $attributes
 * @var array $selected_attributes
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys = array_keys( $attributes );
$user_id        = get_current_user_id();

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="variations_form cart"
			action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post"
			enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>"
			data-product_variations="<?php echo wc_esc_json( wp_json_encode( $available_variations ) ); ?>">
		<?php do_action( 'woocommerce_before_variations_form' ); ?>

		<?php if ( empty( $available_variations ) && false !== $available_variations ) { ?>
			<p class="stock out-of-stock"><?php esc_html_e( 'This product is currently out of stock and unavailable.', 'flexible-subscriptions' ); ?></p>
		<?php } else { ?>
				<?php if ( wp_list_filter( $available_variations, [ 'is_purchasable' => false ] ) ) { ?>
					<p class="limited-subscription-notice notice"><?php esc_html_e( 'You have added a variation of this product to the cart already.', 'flexible-subscriptions' ); ?></p>
				<?php } ?>
				<table class="variations" cellspacing="0">
					<tbody>
					<?php foreach ( $attributes as $attribute_name => $options ) { ?>
						<tr>
							<td class="label"><label
									for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?></label>
							</td>
							<td class="value">
								<?php
								$selected = isset( $_REQUEST[ 'attribute_' . $attribute_name ] ) ? urldecode( sanitize_text_field( wp_unslash( $_REQUEST[ 'attribute_' . $attribute_name ] ) ) ) : $product->get_variation_default_attribute( $attribute_name );
								wc_dropdown_variation_attribute_options(
									[
										'options'   => $options,
										'attribute' => $attribute_name,
										'product'   => $product,
										'selected'  => $selected,
									]
								);
								echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . __( 'Clear', 'flexible-subscriptions' ) . '</a>' ) ) : '';
								?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>

				<div class="single_variation_wrap">
					<?php
					/**
					 * woocommerce_before_single_variation Hook.
					 */
					do_action( 'woocommerce_before_single_variation' );

					/**
					 * woocommerce_single_variation hook. Used to output the cart button and placeholder for variation data.
					 *
					 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
					 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
					 */
					do_action( 'woocommerce_single_variation' );

					/**
					 * woocommerce_after_single_variation Hook.
					 */
					do_action( 'woocommerce_after_single_variation' );
					?>
				</div>
		<?php } ?>

		<?php do_action( 'woocommerce_after_variations_form' ); ?>
	</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
