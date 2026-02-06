<?php
/**
 * Display the billing schedule for a subscription
 *
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $subscription
 * @var \WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface $clock
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$start_date = $subscription->get_start_date() ?? new DateTime( '+1 hour', wp_timezone() );

if ( $subscription->get_billing_frequency()->isEmpty() ) {
	$subscription->set_billing_frequency( new WPInterval( 'P1M' ) );
}

$canEditStartDate = ( $subscription->is_active() === false && $subscription->is_during_first_cycle() && $subscription->is_finalized() === false );
?>
<div id="billing-schedule" class="wc-metaboxes-wrapper">
	<input type="hidden" name="current_period_start" id="current_period_start" value="<?php echo $subscription->get_current_period_start() instanceof \DateTimeInterface ? esc_attr( $subscription->get_current_period_start()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ) : ''; ?>" />
	<table>
		<tbody>
			<tr>
				<th>
					<?php esc_html_e( 'Billing frequency', 'flexible-subscriptions' ); ?>
				</th>
				<td class="billing-frequency">
					<input
						type="number"
						name="billing_interval"
						id="billing_interval"
						min="1"
						value="<?php echo esc_attr( (string) $subscription->get_billing_frequency()->length() ); ?>"
						<?php echo ( $subscription->is_finalized() ) ? 'disabled' : ''; ?>
					/>
					<select
						name="billing_period"
						id="billing_period"
						<?php echo esc_attr( $subscription->is_finalized() ? 'disabled' : '' ); ?>
						>
						<option value="D" <?php selected( $subscription->get_billing_frequency()->unit(), 'D' ); ?>><?php esc_html_e( 'Day(s)', 'flexible-subscriptions' ); ?></option>
						<option value="W" <?php selected( $subscription->get_billing_frequency()->unit(), 'W' ); ?>><?php esc_html_e( 'Week(s)', 'flexible-subscriptions' ); ?></option>
						<option value="M" <?php selected( $subscription->get_billing_frequency()->unit(), 'M' ); ?>><?php esc_html_e( 'Month(s)', 'flexible-subscriptions' ); ?></option>
						<option value="Y" <?php selected( $subscription->get_billing_frequency()->unit(), 'Y' ); ?>><?php esc_html_e( 'Year(s)', 'flexible-subscriptions' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( 'Start Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="start_date"
						id="start_date"
						value="<?php echo esc_attr( $start_date->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ); ?>"
						required
						<?php echo esc_attr( $canEditStartDate ? '' : 'disabled' ); ?>
					/>
				</td>
			</tr>
			<?php if ( $payment_request = wc_get_order( $subscription->get_recent_payment_request_id() ) ) { ?>
			<tr>
				<th>
					<?php esc_html_e( 'Last Payment Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="last_payment_request_date"
						id="last_payment_request_date"
						value="<?php echo esc_attr( $payment_request->get_date_created()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ); ?>"
						disabled
					/>
				</td>
			</tr>
			<?php } ?>
			<?php if ( $subscription->is_finalized() === false || $subscription->has_trial() ) { ?>
			<tr>
				<th>
					<?php esc_html_e( 'Trial End Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="trial_end_date"
						id="trial_end_date"
						value="<?php echo $subscription->get_trial_end_date() instanceof \DateTimeInterface ? esc_attr( $subscription->get_trial_end_date()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ) : ''; ?>"
						<?php echo esc_attr( ( $subscription->is_during_first_cycle() && $subscription->is_finalized() === false ) ? '' : 'disabled' ); ?>
					/>
				</td>
			</tr>
			<?php } ?>
			<?php if ( $subscription->is_finalized() === false ) { ?>
			<tr>
				<th>
					<?php esc_html_e( 'Next Payment Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="current_period_end"
						id="current_period_end"
						value="<?php echo $subscription->get_current_period_end() instanceof \DateTimeInterface ? esc_attr( $subscription->get_current_period_end()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ) : ''; ?>"
						<?php echo esc_attr( $subscription->is_finalized() ? 'disabled' : '' ); ?>
					/>
				</td>
			</tr>
			<?php } ?>
			<?php if ( $subscription->is_cancelled() ) { ?>
			<tr>
				<th>
					<?php esc_html_e( 'Cancelled Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="cancelled_date"
						id="cancelled_date"
						value="<?php echo $subscription->get_cancelled_date() instanceof \DateTimeInterface ? esc_attr( $subscription->get_cancelled_date()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ) : ''; ?>"
						disabled
					/>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<th>
					<?php esc_html_e( 'End Date', 'flexible-subscriptions' ); ?>
				</th>
				<td>
					<input type="datetime-local"
						name="end_date"
						id="end_date"
						value="<?php echo $subscription->get_end_date() instanceof \DateTimeInterface ? esc_attr( $subscription->get_end_date()->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ) ) : ''; ?>"
						<?php echo esc_attr( $subscription->is_finalized() ? 'disabled' : '' ); ?>
					/>
				</td>
			</tr>
		</tbody>
	</table>
</div>
