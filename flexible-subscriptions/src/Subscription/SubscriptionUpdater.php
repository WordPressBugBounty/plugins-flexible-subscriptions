<?php

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class SubscriptionUpdater {

	private Schedule $schedule;

	private SubscriptionLifecycleManager $lifecycle;

	public function __construct( Schedule $schedule, SubscriptionLifecycleManager $lifecycle ) {
		$this->schedule  = $schedule;
		$this->lifecycle = $lifecycle;
	}

	public function update_parent_relation( Subscription $subscription, int $parent_order_id ): void {
		$subscription->set_parent_id( $parent_order_id );
		$parent = $subscription->get_parent();

		if ( $parent ) {
			// translators: %s: parent order number (linked to its details screen).
			$subscription->add_order_note(
				sprintf(
					_x( 'Subscription linked to parent order %s via admin/api.', 'subscription note after linking to a parent order', 'flexible-subscriptions' ),
					sprintf( '<a href="%1$s">#%2$s</a> ', esc_url( $parent->get_edit_order_url() ), $parent->get_order_number() )
				),
				0,
				true
			);
		}
	}

	public function handle_status_change( Subscription $subscription, string $new_status ): void {
		$this->lifecycle->transition( $subscription, $new_status, TransitionContext::manual( 'admin_status_change' ) );
	}

	public function update_schedule( Subscription $subscription, int $interval, string $period ): void {
		$new_frequency = WPInterval::from_spec( new SingleUnitSpec( $interval, $period ) );
		$old_frequency = $subscription->get_billing_frequency();

		if ( $old_frequency->length() === $new_frequency->length() && $old_frequency->unit() === $new_frequency->unit() ) {
			return;
		}

		$subscription->set_billing_frequency( $new_frequency );
		$subscription->recalculate_periods();

		$subscription->save();
		$this->lifecycle->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'schedule_after_update' ) );
	}

	/**
	 * Public reusable method to update dates and trigger recalculations/notes.
	 *
	 * @param Subscription        $subscription
	 * @param \DateTimeImmutable[] $new_dates
	 */
	public function process_date_updates( Subscription $subscription, array $new_dates ): void {
		$labels = [
			'start_date'         => esc_html__( 'Start date', 'flexible-subscriptions' ),
			'current_period_end' => esc_html__( 'Next payment date', 'flexible-subscriptions' ),
			'trial_end_date'     => esc_html__( 'Trial end date', 'flexible-subscriptions' ),
			'end_date'           => esc_html__( 'End date', 'flexible-subscriptions' ),
		];

		$changed_dates = [];

		foreach ( $new_dates as $field => $utc_date ) {
			// @phpstan-ignore method.dynamicName
			$current_date = $subscription->{'get_' . $field}();

			if ( $current_date != $utc_date ) {
				// @phpstan-ignore method.dynamicName
				$subscription->{'set_' . $field}( $utc_date );

				// Prepare display value for note
				$display_date            = $utc_date->setTimezone( wp_timezone() );
				$changed_dates[ $field ] = [
					'label' => $labels[ $field ] ?? $field,
					'value' => date_i18n( get_option( 'date_format' ), $display_date->getTimestamp() ),
				];
			}
		}

		$subscription->save();

		if ( ! empty( $changed_dates ) ) {
			$this->handle_post_save_schedule_logic( $subscription, $changed_dates );
		}
	}

	private function handle_post_save_schedule_logic( Subscription $subscription, array $changed_dates ): void {
		if (
			( array_key_exists( 'trial_end_date', $changed_dates ) && ! array_key_exists( 'current_period_end', $changed_dates ) ) ||
			$subscription->get_current_period() instanceof NullPeriod
		) {
			$subscription->recalculate_periods();
			$subscription->save();
			$this->lifecycle->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'schedule_after_date_recalc' ) );

			$changed_dates['current_period_end'] = [
				'label' => esc_html__( 'Next payment date', 'flexible-subscriptions' ),
				'value' => $subscription->get_current_period_end()->format( get_option( 'date_format' ) ),
			];
		}

		if ( array_key_exists( 'current_period_end', $changed_dates ) ) {
			$this->lifecycle->schedule_next_payment_request_or_transition( $subscription, TransitionContext::system( 'schedule_after_date_update' ) );
		}

		if ( isset( $changed_dates['end_date'] ) ) {
			$this->schedule->cancel( $subscription );
		}

		$note_message = esc_html__( 'Subscription dates updated via admin/api', 'flexible-subscriptions' ) . ': ';
		$date_strings = [];
		foreach ( $changed_dates as $date_data ) {
			$date_strings[] = sprintf( '%1$s: %2$s', $date_data['label'], $date_data['value'] );
		}
		$note_message .= implode( '</br>', $date_strings );
		$subscription->add_order_note( $note_message, 0, true );
	}
}
