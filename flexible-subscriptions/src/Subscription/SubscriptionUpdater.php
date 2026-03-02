<?php

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Actions\UpdateSubscriptionStatus;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class SubscriptionUpdater {

	private SubscriptionRepository $repository;

	private UpdateSubscriptionStatus $update_status;

	public function __construct(
		SubscriptionRepository $repository,
		UpdateSubscriptionStatus $update_status
	) {
		$this->repository    = $repository;
		$this->update_status = $update_status;
	}

	public function update_parent_relation( Subscription $subscription, int $parent_order_id ): void {
		$subscription->set_parent_id( $parent_order_id );
		$parent = $subscription->get_parent();

		if ( $parent ) {
			$origin = $this->detect_update_origin_label();
			// translators: %s: parent order number (linked to its details screen).
			$subscription->add_order_note(
				sprintf(
					// translators: 1: parent order number, 2: update origin label.
					_x( 'Subscription linked to parent order %1$s via %2$s.', 'subscription note after linking to a parent order', 'flexible-subscriptions' ),
					sprintf( '<a href="%1$s">#%2$s</a> ', esc_url( $parent->get_edit_order_url() ), $parent->get_order_number() ),
					$origin
				),
				0,
				true
			);
		}

		$this->repository->save( $subscription );
	}

	public function handle_status_change( Subscription $subscription, string $new_status ): void {
		$this->update_status->execute( $subscription, $new_status, TransitionContext::manual( 'admin_status_change' ) );
	}

	public function update_schedule( Subscription $subscription, int $interval, string $period ): void {
		$new_frequency = WPInterval::from_spec( new SingleUnitSpec( $interval, $period ) );
		$old_frequency = $subscription->get_billing_frequency();

		if ( $old_frequency->equalTo( $new_frequency ) ) {
			return;
		}

		$subscription->update_billing_frequency( $new_frequency );
		$this->repository->save( $subscription );
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
			$changed = $subscription->{'update_' . $field}( $utc_date );
			if ( $changed ) {
				$display_date            = $utc_date->setTimezone( wp_timezone() );
				$changed_dates[ $field ] = [
					'label' => $labels[ $field ] ?? $field,
					'value' => date_i18n( get_option( 'date_format' ), $display_date->getTimestamp() ),
				];
			}
		}

		// todo: flushed events can update some dates and this will not be reflected in note.
		$this->repository->save( $subscription );

		if ( empty( $changed_dates ) ) {
			return;
		}

		$origin       = $this->detect_update_origin_label();
		$note_message = sprintf(
						/* translators: %s: update origin label. */
			esc_html__( 'Subscription dates updated via %s', 'flexible-subscriptions' ),
			$origin
		) . ': ';
		$date_strings = [];
		foreach ( $changed_dates as $date_data ) {
			$date_strings[] = sprintf( '%1$s: %2$s', $date_data['label'], $date_data['value'] );
		}
		$note_message .= implode( '</br>', $date_strings );
		$subscription->add_order_note( $note_message, 0, true );
	}

	private function detect_update_origin_label(): string {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return esc_html__( 'REST API', 'flexible-subscriptions' );
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return esc_html__( 'admin AJAX', 'flexible-subscriptions' );
		}

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return esc_html__( 'admin panel', 'flexible-subscriptions' );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return esc_html__( 'WP-CLI', 'flexible-subscriptions' );
		}

		return esc_html__( 'system', 'flexible-subscriptions' );
	}
}
