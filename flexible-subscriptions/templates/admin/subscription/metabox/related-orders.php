<?php
/**
 * @var \WC_Order[] $payment_requests
 */

use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;

defined( 'ABSPATH' ) || exit;
?>

<style>
.woocommerce_subscriptions_related_orders {
	margin: 0;
	overflow: auto;
}

.woocommerce_subscriptions_related_orders table {
	width: 100%;
	background: #fff;
	border-collapse: collapse;
}

.woocommerce_subscriptions_related_orders table thead th {
	background: #f8f8f8;
	padding: 8px;
	font-size: 11px;
	text-align: left;
	color: #555;
	-webkit-touch-callout: none;
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

.woocommerce_subscriptions_related_orders table thead th:last-child {
	padding-right: 12px;
}

.woocommerce_subscriptions_related_orders table thead th:first-child {
	padding-left: 12px;
}

.woocommerce_subscriptions_related_orders table thead th:last-of-type,
.woocommerce_subscriptions_related_orders table td:last-of-type {
	text-align: right;
}

.woocommerce_subscriptions_related_orders table tbody th,
.woocommerce_subscriptions_related_orders table td {
	padding: 8px;
	text-align: left;
	line-height: 26px;
	vertical-align: top;
	border-bottom: 1px dotted #ececec;
}

.woocommerce_subscriptions_related_orders table tbody th:last-child,
.woocommerce_subscriptions_related_orders table td:last-child {
	padding-right: 12px;
}

.woocommerce_subscriptions_related_orders table tbody th:first-child,
.woocommerce_subscriptions_related_orders table td:first-child {
	padding-left: 12px;
}

.woocommerce_subscriptions_related_orders table tbody tr:last-child td {
	border-bottom: none;
}

</style>
<div class="woocommerce_subscriptions_related_orders">
	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order Number', 'flexible-subscriptions' ); ?></th>
				<th><?php esc_html_e( 'Relationship', 'flexible-subscriptions' ); ?></th>
				<th><?php esc_html_e( 'Date', 'flexible-subscriptions' ); ?></th>
				<th><?php esc_html_e( 'Status', 'flexible-subscriptions' ); ?></th>
				<th><?php echo esc_html_x( 'Total', 'table heading', 'flexible-subscriptions' ); ?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
		<?php foreach ( $payment_requests as $payment_request ) { ?>
		<tr>
	<td>
		<a href="<?php echo esc_url( $payment_request->get_edit_order_url() ); ?>">
			<?php
			// translators: placeholder is an order number.
			printf( esc_html_x( '#%s', 'hash before order number', 'flexible-subscriptions' ), esc_html( $payment_request->get_order_number() ) );
			?>
		</a>
	</td>
	<td>
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
	<td>
			<?php
			$date_created = $payment_request->get_date_created();

			if ( $date_created ) {
				$t_time          = $payment_request->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
				$date_to_display = ucfirst( $date_created->format( get_option( 'date_format' ) ) );
			} else {
				$t_time          = __( 'Unpublished', 'flexible-subscriptions' );
				$date_to_display = $t_time;
			}

			?>
		<abbr title="<?php echo esc_attr( $t_time ); ?>">
			<?php echo esc_html( $date_to_display ); ?>
		</abbr>
	</td>
	<td>
			<?php
			$classes = [
				'order-status',
				sanitize_html_class( 'status-' . $payment_request->get_status() ),
			];

			$status_name = wc_get_order_status_name( $payment_request->get_status() );

			printf( '<mark class="%s"><span>%s</span></mark>', esc_attr( implode( ' ', $classes ) ), esc_html( $status_name ) );
			?>
	</td>
	<td>
		<span class="amount">
			<?php
			echo wp_kses(
				$payment_request->get_formatted_order_total(),
				[
					'small'               => [],
					'span'                => [ 'class' => [] ],
					'del'                 => [],
					'ins'                 => [],
				]
			);
			?>
								</span>
	</td>
</tr>
		<?php } ?>
	</table>
</div>

