<?php
/**
 * Subscription information template
 *
 * @var \WC_Order $order
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription[] $subscriptions
 * @var bool $sent_to_admin
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Date\DefaultDateFormat;
use WPDesk\FlexibleSubscriptions\Formatting\Price\PriceFormat;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( empty( $subscriptions ) ) {
	return;
}

$has_automatic_renewal = false;
?>
<div style="margin-bottom: 40px;">
<h2><?php esc_html_e( 'Subscription information', 'flexible-subscriptions' ); ?></h2>
<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 0.5em;" border="1">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'ID', 'subscription ID table heading', 'flexible-subscriptions' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'Start date', 'table heading', 'flexible-subscriptions' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'End date', 'table heading', 'flexible-subscriptions' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'Recurring total', 'table heading', 'flexible-subscriptions' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $subscriptions as $subscription ) { ?>
		<?php $has_automatic_renewal = $has_automatic_renewal || ! $subscription->is_manual(); ?>
		<tr>
			<td class="td" scope="row" style="text-align:left;"><a href="<?php echo esc_url( ( $sent_to_admin ) ? $subscription->get_edit_order_url() : $subscription->get_view_order_url() ); ?>"><?php printf( esc_html_x( '#%s', 'subscription number in email table. (eg: #106)', 'flexible-subscriptions' ), esc_html( $subscription->get_order_number() ) ); ?></a></td>
				<td class="td" scope="row" style="text-align:left;">
					<?php echo esc_html( new DefaultDateFormat( $subscription->get_start_date() ) ); ?>
				</td>
				<td class="td" scope="row" style="text-align:left;">
					<?php
					$end = $subscription->get_end_date();
					if ( $end instanceof \DateTimeInterface ) {
						echo esc_html( new DefaultDateFormat( $end ) );
					} else {
						echo esc_html_x( 'When cancelled', 'Used as end date for an indefinite subscription', 'flexible-subscriptions' );
					}
					?>
				</td>
			<td class="td" scope="row" style="text-align:left;">
				<?php echo wp_kses_post( (string) PriceFormat::subscription( $subscription ) ); ?>
			</td>
		</tr>
	<?php } ?>
</tbody>
</table>
<?php
if ( $has_automatic_renewal && ! $sent_to_admin ) { // && $subscription->get_time( 'next_payment' ) > 0 ) {
	if ( count( $subscriptions ) === 1 ) {
		$subscription   = reset( $subscriptions );
		$my_account_url = $subscription->get_view_order_url();
	} else {
		$my_account_url = wc_get_endpoint_url( 'subscriptions', '', wc_get_page_permalink( 'myaccount' ) );
	}

	// Translators: Placeholders are opening and closing My Account link tags.
	printf(
		'<small>%s</small>',
		wp_kses_post(
			sprintf(
				_n(
					'This subscription is set to renew automatically using your payment method on file. You can manage or cancel this subscription from your %1$saccount page%2$s.',
					'These subscriptions are set to renew automatically using your payment method on file. You can manage or cancel your subscriptions from your %1$saccount page%2$s.',
					count( $subscriptions ),
					'flexible-subscriptions'
				),
				'<a href="' . $my_account_url . '">',
				'</a>'
			)
		)
	);
}
?>
</div>



