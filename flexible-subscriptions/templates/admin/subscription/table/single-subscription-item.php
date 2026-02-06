<?php
/**
 * @var \WC_Order_Item_Product $subscription_item
 */

use WPDesk\FlexibleSubscriptions\Utils\OrderItemUtil;

defined( 'ABSPATH' ) || exit;
?>

<div class="order-item">
	<a href="<?php echo esc_url( OrderItemUtil::get_product_link( $subscription_item ) ); ?>">
		<?php if ( $subscription_item['qty'] > 1 ) { ?>
			<?php echo absint( $subscription_item['qty'] ) . ' &times; '; ?>
		<?php } ?>
		<?php echo wp_kses_post( OrderItemUtil::get_name( $subscription_item ) ); ?>
	</a>
	<?php
	$item_meta = OrderItemUtil::get_formatted_item_meta( $subscription_item );
	if ( $item_meta ) {
		echo wc_help_tip( $item_meta, true );
	}
	?>
</div>
