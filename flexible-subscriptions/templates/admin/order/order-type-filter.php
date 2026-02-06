<?php
/**
 * @var array<string, string> $order_types
 * @var string $selected
 */
?>
<select name='shop_order_subtype' id='dropdown_shop_order_subtype'>
	<option value=""><?php esc_html_e( 'All orders types', 'flexible-subscriptions' ); ?></option>

	<?php foreach ( $order_types as $order_type_key => $order_type_description ) { ?>
	<option
		value="<?php echo esc_attr( $order_type_key ); ?>"
		<?php selected( $selected, $order_type_key ); ?>
		>
		<?php echo esc_html( $order_type_description ); ?>
	</option>
	<?php } ?>

</select>
