<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription[] $subscriptions
 */

use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\HumanFriendlyFormat;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<header>
	<h2><?php esc_html_e( 'Related subscriptions', 'flexible-subscriptions' ); ?></h2>
</header>

<table class="shop_table shop_table_responsive my_account_orders woocommerce-orders-table woocommerce-MyAccount-subscriptions woocommerce-orders-table--subscriptions">
	<thead>
		<tr>
			<th class="subscription-id order-number woocommerce-orders-table__header woocommerce-orders-table__header-order-number woocommerce-orders-table__header-subscription-id"><span class="nobr"><?php esc_html_e( 'Subscription', 'flexible-subscriptions' ); ?></span></th>
			<th class="subscription-status order-status woocommerce-orders-table__header woocommerce-orders-table__header-order-status woocommerce-orders-table__header-subscription-status"><span class="nobr"><?php esc_html_e( 'Status', 'flexible-subscriptions' ); ?></span></th>
			<th class="subscription-next-payment order-date woocommerce-orders-table__header woocommerce-orders-table__header-order-date woocommerce-orders-table__header-subscription-next-payment"><span class="nobr"><?php echo esc_html_x( 'Next payment', 'table heading', 'flexible-subscriptions' ); ?></span></th>
			<th class="subscription-total order-total woocommerce-orders-table__header woocommerce-orders-table__header-order-total woocommerce-orders-table__header-subscription-total"><span class="nobr"><?php echo esc_html_x( 'Total', 'table heading', 'flexible-subscriptions' ); ?></span></th>
			<th class="subscription-actions order-actions woocommerce-orders-table__header woocommerce-orders-table__header-order-actions woocommerce-orders-table__header-subscription-actions">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $subscriptions as $subscription ) : ?>
			<?php $current_period = $subscription->get_current_period(); ?>
			<tr class="order woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $subscription->get_status() ); ?>">
				<td class="subscription-id order-number woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-id woocommerce-orders-table__cell-order-number" data-title="<?php esc_attr_e( 'ID', 'flexible-subscriptions' ); ?>">
					<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>">
						<?php printf( esc_html_x( '#%s', 'hash before order number', 'flexible-subscriptions' ), esc_html( $subscription->get_order_number() ) ); ?>
					</a>
				</td>
				<td class="subscription-status order-status woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-status woocommerce-orders-table__cell-order-status" style="white-space:nowrap;" data-title="<?php esc_attr_e( 'Status', 'flexible-subscriptions' ); ?>">
					<?php echo esc_html( Status::nice_name( $subscription->get_status() ) ); ?>
				</td>
				<td class="subscription-next-payment order-date woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-next-payment woocommerce-orders-table__cell-order-date" data-title="<?php echo esc_attr_x( 'Next payment', 'table heading', 'flexible-subscriptions' ); ?>">
				<?php echo $current_period->getEndDate() ? esc_html( new HumanFriendlyFormat( $current_period->getEndDate() ) ) : '-'; ?>
				</td>
				<td class="subscription-total order-total woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-total woocommerce-orders-table__cell-order-total" data-title="<?php echo esc_attr_x( 'Total', 'Used in data attribute. Escaped', 'flexible-subscriptions' ); ?>">
					<?php echo wp_kses_post( $subscription->get_formatted_order_total() ); ?>
				</td>
				<td class="subscription-actions order-actions woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-actions woocommerce-orders-table__cell-order-actions">
					<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>" class="woocommerce-button button view"><?php echo esc_html_x( 'View', 'view a subscription', 'flexible-subscriptions' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
