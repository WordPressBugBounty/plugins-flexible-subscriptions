<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\PermanentDismissibleNotice;

/**
 * Displays an admin notice for failed payment requests.
 */
class FailedPaymentRequestNotice implements HookProvider {

	public function hooks(): void {
		add_action( 'admin_notices', [ $this, 'display_admin_notice' ] );
		add_action( 'wpdesk_notice_dismissed_notice', [ $this, 'clear_transients' ], 10, 1 );
	}

	public function clear_transients( $notice_name ): void {
		if ( $notice_name !== 'flexible-subscriptions-failed-payment-request-notice' ) {
			return;
		}

		$transient_keys = $this->get_failures();

		foreach ( $transient_keys as $transient ) {
			delete_transient( str_replace( '_transient_', '', $transient->option_name ) );
		}
	}

	public function display_admin_notice(): void {
		$transient_keys          = $this->get_failures();
		$failed_payment_requests = 0;

		foreach ( $transient_keys as $transient ) {
			$error_data = maybe_unserialize( $transient->option_value );

			if ( ! empty( $error_data ) && is_array( $error_data ) && isset( $error_data['subscription_id'], $error_data['message'] ) ) {
				++$failed_payment_requests;
			}
		}

		if ( $failed_payment_requests > 0 ) {
			$notice_message = sprintf(
				// translators: %1$d number of failure occurrences
				_n(
					'<strong>There was %1$d failed payment request when processing subscription renewal.</strong> Please check the <a href="%2$s"><em>WooCommerce logs</em></a> for details.',
					'<strong>There were %1$d failed payment requests when processing subscription renewal.</strong> Please check the <a href="%2$s"><em>WooCommerce logs</em></a> for details.',
					$failed_payment_requests,
					'flexible-subscriptions'
				),
				absint( $failed_payment_requests ),
				esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) )
			);

			new PermanentDismissibleNotice(
				$notice_message,
				'flexible-subscriptions-failed-payment-request-notice',
				'error'
			);
		}
	}

	private function get_failures(): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT option_name, option_value FROM %i WHERE option_name LIKE %s',
				$wpdb->options,
				'_transient_fsub_payment_request_error_%'
			)
		);
	}
}
