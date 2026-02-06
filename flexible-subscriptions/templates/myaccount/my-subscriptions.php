<?php
/**
 * My Subscriptions section on the My Account page
 *
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription[] $subscriptions
 * @var int $max_num_pages
 * @var int $current_page
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\HumanFriendlyFormat;
use WPDesk\FlexibleSubscriptions\Formatting\Price\PriceFormat;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="woocommerce_account_subscriptions">

	<?php if ( ! empty( $subscriptions ) ) : ?>
	<table class="my_account_subscriptions my_account_orders woocommerce-orders-table woocommerce-MyAccount-subscriptions shop_table shop_table_responsive woocommerce-orders-table--subscriptions">

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
		<?php foreach ( $subscriptions as $subscription ) { ?>
			<?php $current_period = $subscription->get_current_period(); ?>
		<tr class="order woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $subscription->get_status() ); ?>">
			<td class="subscription-id order-number woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-id woocommerce-orders-table__cell-order-number" data-title="<?php esc_attr_e( 'ID', 'flexible-subscriptions' ); ?>">
				<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>"><?php echo esc_html( sprintf( _x( '#%s', 'hash before order number', 'flexible-subscriptions' ), $subscription->get_order_number() ) ); ?></a>
			</td>
			<td class="subscription-status order-status woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-status woocommerce-orders-table__cell-order-status" data-title="<?php esc_attr_e( 'Status', 'flexible-subscriptions' ); ?>">
				<?php echo esc_attr( Status::nice_name( $subscription->get_status() ) ); ?>
			</td>
			<td class="subscription-next-payment order-date woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-next-payment woocommerce-orders-table__cell-order-date" data-title="<?php echo esc_attr_x( 'Next Payment', 'table heading', 'flexible-subscriptions' ); ?>">
					<?php echo $current_period->getEndDate() ? esc_attr( new HumanFriendlyFormat( $current_period->getEndDate() ) ) : '-'; ?>
					<?php if ( ! $subscription->is_manual() && $subscription->is_active() /** && $subscription->get_time( 'next_payment' ) > 0 */ ) : ?>
				<br/><small><?php echo esc_attr( $subscription->get_payment_method_to_display() ); ?></small>
				<?php endif; ?>
			</td>
			<td class="subscription-total order-total woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-total woocommerce-orders-table__cell-order-total" data-title="<?php echo esc_attr_x( 'Total', 'Used in data attribute. Escaped', 'flexible-subscriptions' ); ?>">
				<?php echo wp_kses_post( (string) PriceFormat::subscription( $subscription ) ); ?>
			</td>
			<td class="subscription-actions order-actions woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-actions woocommerce-orders-table__cell-order-actions">
				<a href="<?php echo esc_url( $subscription->get_view_order_url() ); ?>" class="woocommerce-button button view"><?php echo esc_html_x( 'View', 'view a subscription', 'flexible-subscriptions' ); ?></a>
			</td>
		</tr>
	<?php } ?>
	</tbody>

	</table>
		<?php if ( 1 < $max_num_pages ) : ?>
			<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'fsb-subscriptions', $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'flexible-subscriptions' ); ?></a>
			<?php endif; ?>

			<?php if ( intval( $max_num_pages ) !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'fsb-subscriptions', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'flexible-subscriptions' ); ?></a>
			<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p class="no_subscriptions woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<?php
			if ( 1 < $current_page ) :
				printf( esc_html__( 'You have reached the end of subscriptions. Go to the %1$sfirst page%2$s.', 'flexible-subscriptions' ), '<a href="' . esc_url( wc_get_endpoint_url( 'subscriptions', 1 ) ) . '">', '</a>' );
			else :
				esc_html_e( 'You have no active subscriptions.', 'flexible-subscriptions' );
				?>
				<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'Browse products', 'flexible-subscriptions' ); ?>
				</a>
				<?php
		endif;
			?>
		</p>

	<?php endif; ?>

</div>
