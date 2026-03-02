<?php // phpcs:ignoreFile WordPress.Security.NonceVerification.Missing -- Nonces are verified in attached hook parents.

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use DateTimeImmutable;
use DateTimeZone;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionUpdater;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Whenever admin saves subscription metaboxes with an update action, we
 * want to save additional available subscription data.
 */
class AdminSubscriptionUpdate implements HookProvider {

	private SubscriptionFinder $finder;
	private SubscriptionUpdater $updater;

	public function __construct( SubscriptionFinder $finder, SubscriptionUpdater $updater ) {
		$this->finder   = $finder;
		$this->updater = $updater;
	}

	public function hooks(): void {
		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'ensure_order_status' ], 5, 1 );
		add_action( 'woocommerce_process_shop_order_meta', $this, 40, 1 );
	}

	/** @param int $subscription_id */
	public function ensure_order_status( $subscription_id ): void {
		if ( Subscription::OBJECT_TYPE !== get_post_type( $subscription_id ) ) {
			return;
		}

		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( empty( $_POST['order_status'] ) ) {
			$_POST['order_status'] = Status::PENDING;
		}
	}

	/** @param int $subscription_id */
	public function __invoke( $subscription_id ): void {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $subscription_id ) ) {
			return;
		}

		$subscription = $this->finder->find( $subscription_id );

		if ( ! $subscription instanceof Subscription ) {
			return;
		}

		try {
			/**
			 * Filters the payment meta data before saving the subscription.
			 *
			 * @param array        $post_data    The `$_POST` superglobal array.
			 * @param Subscription $subscription The subscription object being processed.
			 * @param string       $payment_method The slug of the payment method.
			 *
			 * @since 1.6.0
			 */
			do_action( 'fsub/subscription/validate_payment_meta', $_POST, $subscription, $subscription->get_payment_method() );
		} catch ( \Exception $e ) {
			$subscription->add_order_note( $e->getMessage(), 0, true );
		}

		if ( ! empty( $_POST['parent-order-id'] ) ) {
			$this->updater->update_parent_relation( $subscription, absint( wp_unslash( $_POST['parent-order-id'] ) ) );
		}

		if ( isset( $_POST['order_status'] ) ) {
			$new_status = str_replace( 'wc-', '', sanitize_text_field( wp_unslash( $_POST['order_status'] ) ) );
			$this->updater->handle_status_change( $subscription, $new_status );
		}

		if ( isset( $_POST['billing_interval'] ) || isset( $_POST['billing_period'] ) ) {
			$interval = isset( $_POST['billing_interval'] ) ? (int) wp_unslash( $_POST['billing_interval'] ) : $subscription->get_billing_frequency()->length();
			$period   = isset( $_POST['billing_period'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_period'] ) ) : $subscription->get_billing_frequency()->unit();

			$this->updater->update_schedule( $subscription, $interval, $period );
		}

		$dates_to_update = [];
		$fields          = [ 'start_date', 'current_period_end', 'trial_end_date', 'end_date' ];

		foreach ( $fields as $field ) {
			if ( ! empty( $_POST[ $field ] ) ) {
				$date_str = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				try {
					$date_obj = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $date_str, wp_timezone() );
					if ( $date_obj ) {
						$dates_to_update[ $field ] = $date_obj->setTimezone( new DateTimeZone( 'UTC' ) );
					}
				} catch ( \Exception $e ) {
					continue;
				}
			}
		}

		$this->updater->process_date_updates( $subscription, $dates_to_update );
	}
}
