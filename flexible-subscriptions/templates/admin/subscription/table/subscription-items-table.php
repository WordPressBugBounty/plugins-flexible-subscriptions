<?php
/**
 * @var \WC_Order_Item_Product[] $subscription_items
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $the_subscription
 */

use WPDesk\FlexibleSubscriptions\Utils\OrderItemUtil;

defined( 'ABSPATH' ) || exit;
?>

<a href="#" class="show_order_items">
	<?php
	// translators: Number of items in the subscription order.
	printf( esc_html( _n( '%d item', '%d items', $the_subscription->get_item_count(), 'flexible-subscriptions' ) ), absint( $the_subscription->get_item_count() ) );
	?>
</a>
<table class="order_items" cellspacing="0">
	<?php foreach ( $subscription_items as $item ) { ?>
<tr>
	<td class="qty"><?php echo absint( $item['qty'] ); ?></td>
	<td class="name">
	<a href="<?php echo esc_url( OrderItemUtil::get_product_link( $item ) ); ?>">
		<?php echo wp_kses_post( OrderItemUtil::get_name( $item ) ); ?>
	</a>
		<?php
		$item_meta = OrderItemUtil::get_formatted_item_meta( $item );
		if ( $item_meta ) {
			echo wc_help_tip( $item_meta, true );
		}
		?>
	</td>
</tr>
	<?php } ?>
</table>
