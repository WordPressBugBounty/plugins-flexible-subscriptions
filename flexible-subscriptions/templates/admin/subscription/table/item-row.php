<?php
/**
 * @var \WC_Order_Item_Product $item
 * @var string $item_name
 * @var string $item_meta_html
 */

defined( 'ABSPATH' ) || exit;
?>

<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_admin_order_item_class', '', $item ) ); ?>">
	<td class="qty"><?php echo absint( $item['qty'] ); ?></td>
	<td class="name">
		<?php

		echo wp_kses( $item_name, [ 'a' => [ 'href' => [] ] ] );

		$item_meta_html = wc_display_item_meta(
			$item,
			[
				'before'    => '',
				'after'     => '',
				'separator' => '',
				'echo'      => false,
			]
		);

		if ( $item_meta_html ) {
			echo wc_help_tip( $item_meta_html, true );
		}
		?>
	</td>
</tr>
