<?php
/**
 * @var int $loop
 * @var \WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper $product
 */

use WPDesk\FlexibleSubscriptions\Product\ProductEditTips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="fsb-variable-fields-group show_if_fsb-variable-subscription">
	<?php
		woocommerce_wp_text_input(
			[
				'id'                => sprintf( 'fsb_variable_subscription_sign_up_fee[%d]', esc_attr( (string) $loop ) ),
				'class'             => 'wc_input_price',
				'label'             => sprintf( __( 'Sign-up fee (%s)', 'flexible-subscriptions' ), get_woocommerce_currency_symbol() ),
				'value'             => $product->get_signup_fee(),
				'wrapper_class'     => 'form-row form-row-full short',
				'description'       => ProductEditTips::sign_up_fee(),
				'desc_tip'          => true,
				'type'              => 'text',
				'data_type'         => 'price',
				'custom_attributes' => [
					'step' => 'any',
					'min'  => '0',
				],
			]
		);
		?>
</div>
