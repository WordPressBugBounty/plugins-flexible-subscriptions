<?php

use WPDesk\FlexibleSubscriptions\Product\ProductEditTips;

defined( 'ABSPATH' ) || exit;

woocommerce_wp_text_input(
	[
		'id'                => '_fsb_subscription_sign_up_fee',
		'class'             => 'wc_input_price short',
		// translators: %s is a currency symbol / code.
		'label'             => sprintf( __( 'Sign-up fee (%s)', 'flexible-subscriptions' ), get_woocommerce_currency_symbol() ),
		'placeholder'       => _x( 'e.g. 9.90', 'example price', 'flexible-subscriptions' ),
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
