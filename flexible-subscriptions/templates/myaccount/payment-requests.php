<?php
/**
 * @var \WC_Order[] $payment_requests
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $subscription
 */

use WPDesk\FlexibleSubscriptions\Formatting\Price\PaymentRequestTotal;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;

defined( 'ABSPATH' ) || exit;
?>
<header>
	<h2><?php esc_html_e( 'Subscription history', 'flexible-subscriptions' ); ?></h2>
</header>

<table class="shop_table shop_table_responsive my_account_orders woocommerce-orders-table woocommerce-MyAccount-orders woocommerce-orders-table--orders">

	<thead>
		<tr>
			<th class="order-number woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'Order', 'flexible-subscriptions' ); ?></span></th>
			<th class="order-date woocommerce-orders-table__header woocommerce-orders-table__header-order-date woocommerce-orders-table__header-order-date"><span class="nobr"><?php esc_html_e( 'Date', 'flexible-subscriptions' ); ?></span></th>
			<th class="order-date woocommerce-orders-table__header woocommerce-orders-table__header-order-date woocommerce-orders-table__header-order-date"><span class="nobr"><?php esc_html_e( 'Relationship', 'flexible-subscriptions' ); ?></span></th>
			<th class="order-status woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?php esc_html_e( 'Status', 'flexible-subscriptions' ); ?></span></th>
			<th class="order-total woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?php echo esc_html_x( 'Total', 'table heading', 'flexible-subscriptions' ); ?></span></th>
			<th class="order-actions woocommerce-orders-table__header woocommerce-orders-table__header-order-actions">&nbsp;</th>
		</tr>
	</thead>

	<tbody>
		<?php
		foreach ( $payment_requests as $payment_request ) {
			$order = wc_get_order( $payment_request );

			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$item_count = $order->get_item_count();
			$order_date = $order->get_date_created();

			?>
			<tr class="order woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order->get_status() ); ?>">
				<td class="order-number woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="<?php esc_attr_e( 'Order Number', 'flexible-subscriptions' ); ?>">
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						<?php printf( esc_html_x( '#%s', 'hash before order number', 'flexible-subscriptions' ), esc_html( $order->get_order_number() ) ); ?>
					</a>
				</td>
				<td class="order-date woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="<?php esc_attr_e( 'Date', 'flexible-subscriptions' ); ?>">
				<time datetime="<?php echo esc_attr( $order_date->date( 'Y-m-d' ) ); ?>" title="<?php echo esc_attr( (string) $order_date->getTimestamp() ); ?>">
					<?php echo wp_kses_post( $order_date->date_i18n( wc_date_format() ) ); ?>
				</time>
				</td>
				<td class="order-status woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="<?php esc_attr_e( 'Relationship', 'flexible-subscriptions' ); ?>" style="white-space:nowrap;">
				<?php
				$renewal = Renewal::from_order( $payment_request );
				$subtype = $renewal instanceof Renewal ? __( 'Renewal', 'flexible-subscriptions' ) : $payment_request->get_meta( Renewal::META_ORDER_TYPE );
				if ( $subtype === '' || $subtype === Renewal::ORDER_TYPE_VALUE ) {
					$subtype = __( 'Renewal', 'flexible-subscriptions' );
				}

				echo esc_html(
					apply_filters(
						'fsub/admin/order/relationship',
						$subtype,
						$payment_request
					)
				);
				?>
				</td>
				<td class="order-status woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="<?php esc_attr_e( 'Status', 'flexible-subscriptions' ); ?>" style="white-space:nowrap;">
					<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
				</td>
				<td class="order-total woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="<?php echo esc_attr_x( 'Total', 'Used in data attribute. Escaped', 'flexible-subscriptions' ); ?>">
					<?php
					echo wp_kses_post( (string) new PaymentRequestTotal( $payment_request ) );
					?>
				</td>
				<td class="order-actions woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
					<?php
					$actions = [];

					if ( $order->needs_payment() && $order->get_id() === $subscription->get_recent_payment_request_id() ) {
						$actions['pay'] = [
							'url'  => $order->get_checkout_payment_url(),
							'name' => esc_html_x( 'Pay', 'pay for a subscription', 'flexible-subscriptions' ),
						];
					}

					if ( in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_cancel', [ 'pending', 'failed' ], $order ), true ) ) {
						$redirect = wc_get_page_permalink( 'myaccount' );

						$redirect = $subscription->get_view_order_url();

						$actions['cancel'] = [
							'url'  => $order->get_cancel_order_url( $redirect ),
							'name' => esc_html_x( 'Cancel', 'an action on a subscription', 'flexible-subscriptions' ),
						];
					}

					$actions['view'] = [
						'url'  => $order->get_view_order_url(),
						'name' => esc_html_x( 'View', 'view a subscription', 'flexible-subscriptions' ),
					];

					$actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );

					if ( $actions ) {
						foreach ( $actions as $key => $action ) {
							echo wp_kses_post( '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>' );
						}
					}
					?>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
