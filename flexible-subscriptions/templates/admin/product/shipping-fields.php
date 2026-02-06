<?php
use WPDesk\FlexibleSubscriptions\Product\ProductEditTips;

defined( 'ABSPATH' ) || exit;
?>
<div class="fsb_one_time_shipping options_group show_if_fsb-subscription show_if_fsb-variable-subscription">
	<?php
	woocommerce_wp_checkbox(
		[
			'id'            => '_fsb_subscription_one_time_shipping',
			'label'         => __( 'One time shipping', 'flexible-subscriptions' ),
			'wrapper_class' => 'show_if_fsb-subscription show_if_fsb-variable-subscription',
			'description'   => ProductEditTips::one_time_shipping(),
			'desc_tip'      => true,
		]
	);
	?>
</div>
