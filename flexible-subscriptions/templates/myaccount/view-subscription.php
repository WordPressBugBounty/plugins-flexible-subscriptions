<?php
/**
 * View Subscription
 *
 * Shows the details of a particular subscription on the account page
 *
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $subscription
 * @var \WC_Order[] $payment_requests
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\HumanFriendlyFormat;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wc_print_notices();

?>
<table class="shop_table subscription_details">
	<tbody>
		<tr>
			<td><?php esc_html_e( 'Status', 'flexible-subscriptions' ); ?></td>
			<td><?php echo esc_html( Status::nice_name( $subscription->get_status() ) ); ?></td>
		</tr>
		<?php
		$dates_to_display = [
			'start_date'                   => _x( 'Start date', 'customer subscription table header', 'flexible-subscriptions' ),
			'last_order_date_created'      => _x( 'Last order date', 'customer subscription table header', 'flexible-subscriptions' ),
			'current_period_end'           => _x( 'Next payment date', 'customer subscription table header', 'flexible-subscriptions' ),
			'trial_end_date'               => _x( 'Trial end date', 'customer subscription table header', 'flexible-subscriptions' ),
			'end_date'                     => _x( 'End date', 'customer subscription table header', 'flexible-subscriptions' ),
		];
		foreach ( $dates_to_display as $date_type => $date_title ) {
			if ( method_exists( $subscription, 'get_' . $date_type ) ) {
				// @phpstan-ignore method.dynamicName
				$date = $subscription->{"get_$date_type"}();
			} else {
				continue;
			}
			?>
			<?php if ( $date instanceof \DateTimeInterface ) { ?>
				<tr>
					<td><?php echo esc_html( $date_title ); ?></td>
					<td><?php echo esc_html( new HumanFriendlyFormat( $date ) ); ?></td>
				</tr>
			<?php } ?>
		<?php } ?>
		<?php if ( $subscription->get_current_period()->getEndDate() ) : ?>
			<tr>
				<td><?php esc_html_e( 'Payment', 'flexible-subscriptions' ); ?></td>
				<td>
					<span data-is_manual="<?php echo esc_attr( wc_bool_to_string( $subscription->is_manual() ) ); ?>" class="subscription-payment-method"><?php echo esc_html( $subscription->get_payment_method_to_display() ); ?></span>
				</td>
			</tr>
		<?php endif; ?>
		<?php $actions = []; ?>
		<?php
		if ( ! $subscription->is_finalized() ) {
			$actions['cancel'] = [
				'url'  => wp_nonce_url(
					add_query_arg(
						[
							'id'     => $subscription->get_id(),
							'action' => 'customer_cancel_subscription',
						],
						admin_url( 'admin-post.php' )
					),
					'fsb_cancel_subscription'
				),
				'name' => _x( 'Cancel', 'cancel subscription', 'flexible-subscriptions' ),
			];
		}
		?>
		<?php if ( ! empty( $actions ) ) : ?>
			<tr>
				<td><?php esc_html_e( 'Actions', 'flexible-subscriptions' ); ?></td>
				<td>
					<?php foreach ( $actions as $key => $action ) { ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>" class="button <?php echo esc_attr( sanitize_html_class( $key ) ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
					<?php } ?>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( $notes = $subscription->get_customer_order_notes() ) : ?>
	<h2><?php esc_html_e( 'Subscription updates', 'flexible-subscriptions' ); ?></h2>
	<ol class="woocommerce-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
		<li class="woocommerce-OrderUpdate comment note">
			<div class="woocommerce-OrderUpdate-inner comment_container">
				<div class="woocommerce-OrderUpdate-text comment-text">
					<p class="woocommerce-OrderUpdate-meta meta"><?php echo esc_html( date_i18n( _x( 'l jS \o\f F Y, h:ia', 'date on subscription updates list. Will be localized', 'flexible-subscriptions' ), ( new DateTime( $note->comment_date, new \DateTimeZone( 'UTC' ) ) )->getTimestamp() ) ); ?></p>
					<div class="woocommerce-OrderUpdate-description description">
						<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
					</div>
						<div class="clear"></div>
					</div>
				<div class="clear"></div>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php
$totals = $subscription->get_order_item_totals();

// Don't display the payment method as it is included in the main subscription details table.
unset( $totals['payment_method'] );
?>
<h2><?php esc_html_e( 'Subscription totals', 'flexible-subscriptions' ); ?></h2>

<table class="shop_table order_details">
	<thead>
		<tr>
			<th class="product-name"><?php echo esc_html_x( 'Product', 'table headings in notification email', 'flexible-subscriptions' ); ?></th>
			<th class="product-total"><?php echo esc_html_x( 'Total', 'table heading', 'flexible-subscriptions' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $subscription->get_items() as $item_id => $item ) {
			$_product = $item->get_product();
			if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $subscription ) ); ?>">
					<td class="product-name">
						<?php
						if ( $_product && ! $_product->is_visible() ) {
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ) );
						} else {
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', sprintf( '<a href="%s">%s</a>', get_permalink( $item['product_id'] ), $item['name'] ), $item, false ) );
						}

						echo wp_kses_post( apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', $item['qty'] ) . '</strong>', $item ) );

						/**
						 * Allow other plugins to add additional product information here.
						 *
						 * @param int $item_id The subscription line item ID.
						 * @param WC_Order_Item|array $item The subscription line item.
						 * @param \WPDesk\FlexibleSubscriptions\Subscription\Subscription $subscription The subscription.
						 * @param bool $plain_text Wether the item meta is being generated in a plain text context.
						 */
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $subscription, false );

						wc_display_item_meta( $item );

						/**
						 * Allow other plugins to add additional product information here.
						 *
						 * @param int $item_id The subscription line item ID.
						 * @param WC_Order_Item|array $item The subscription line item.
						 * @param \WPDesk\FlexibleSubscriptions\Subscription\Subscription $subscription The subscription.
						 * @param bool $plain_text Wether the item meta is being generated in a plain text context.
						 */
						do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription, false );
						?>
					</td>
					<td class="product-total">
						<?php echo wp_kses_post( $subscription->get_formatted_line_subtotal( $item ) ); ?>
					</td>
				</tr>
				<?php
			}

			if ( $subscription->has_status( [ 'completed', 'processing' ] ) && ( $purchase_note = get_post_meta( $_product->id, '_purchase_note', true ) ) ) {
				?>
				<tr class="product-purchase-note">
					<td colspan="3"><?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
		<tfoot>
		<?php foreach ( $totals as $key => $total ) { ?>
			<tr>
				<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
				<td><?php echo wp_kses_post( $total['value'] ); ?></td>
			</tr>
		<?php } ?>
	</tfoot>
</table>

<?php
if ( ! empty( $payment_requests ) ) {
	$this->output_render(
		'myaccount/payment-requests',
		[
			'payment_requests'                => $payment_requests,
			'subscription'                    => $subscription,
		]
	);
}

do_action( 'fsub/view/myaccount/view-subscription', $subscription );

wc_get_template( 'order/order-details-customer.php', [ 'order' => $subscription ] );
?>

<div class="clear"></div>
