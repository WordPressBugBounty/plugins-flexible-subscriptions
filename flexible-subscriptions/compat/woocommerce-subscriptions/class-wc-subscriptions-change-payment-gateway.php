<?php

class WC_Subscriptions_Change_Payment_Gateway {

	// This flag might be checked by some gateways before initiating change payment flow.
	public static $is_request_to_change_payment = false;

	/**
	 * Persist WCS-style payment meta on a subscription.
	 *
	 * WCS expects gateway-specific data to be stored as post meta on the subscription (e.g. Stripe stores
	 * `_stripe_customer_id` and `_stripe_source_id`). Gateways provide this in a structured array under
	 * the `post_meta` key.
	 *
	 * @param WC_Subscription $subscription
	 * @param array           $payment_meta
	 * @return void
	 */
	private static function set_payment_meta( WC_Subscription $subscription, array $payment_meta ): void {
		foreach ( $payment_meta as $meta_table => $meta ) {
			foreach ( $meta as $meta_key => $meta_data ) {
				if ( isset( $meta_data['value'] ) ) {
					switch ( $meta_table ) {
						case 'user_meta':
						case 'usermeta':
							update_user_meta( $subscription->get_user_id(), $meta_key, $meta_data['value'] );
							break;
						case 'post_meta':
						case 'postmeta':
							$subscription->update_meta_data( $meta_key, $meta_data['value'] );
							break;
						case 'options':
							update_option( $meta_key, $meta_data['value'] );
							break;
						default:
							do_action( 'wcs_save_other_payment_meta', $subscription, $meta_table, $meta_key, $meta_data['value'] );
					}
				}
			}
		}

		// Save the subscription to persist post meta changes.
		$subscription->save();
	}

	/**
	 * Update the recurring payment method on a subscription.
	 * This now interacts with the underlying FSB Subscription object.
	 *
	 * @param WC_Subscription $subscription An instance of the compat WC_Subscription object.
	 * @param string          $new_payment_method The ID of the new payment method gateway.
	 * @param array           $new_payment_method_meta Optional. Gateway-specific metadata (tokens, etc.). Default false.
	 * @return bool|WP_Error True on success, WP_Error or false on failure.
	 */
	public static function update_payment_method( $subscription, $new_payment_method, $new_payment_method_meta = [] ) {
		if ( ! $subscription instanceof WC_Subscription ) {
			return new \WP_Error( 'fsb_compat_error', 'Invalid subscription object passed to update_payment_method.' );
		}

		$old_payment_method = $subscription->get_payment_method();
		$old_payment_method_title          = $subscription->get_payment_method_title();
		$new_gateway        = WC()->payment_gateways()->get_available_payment_gateways()[ $new_payment_method ] ?? null;

		if ( ! $new_gateway ) {
			return new \WP_Error( 'fsb_compat_error', 'Invalid payment gateway specified.' );
		}

		$subscription->set_payment_method( $new_payment_method );
		$subscription->set_payment_method_title( $new_gateway->get_title() );

		$payment_meta_valid = true;
		$validation_error   = null;
		try {
			do_action( 'woocommerce_subscription_validate_payment_meta', $new_payment_method, $new_payment_method_meta, $subscription );
			do_action( 'woocommerce_subscription_validate_payment_meta_' . $new_payment_method, $new_payment_method_meta, $subscription );
		} catch ( Exception $e ) {
			$payment_meta_valid = false;
			$validation_error   = $e->getMessage();
		}

		if ( ! $payment_meta_valid ) {
			$subscription->set_payment_method( $old_payment_method );
			$subscription->set_payment_method_title( $old_payment_method_title );

			return new \WP_Error( 'fsb_compat_payment_meta_invalid', $validation_error ?: __( 'Invalid payment method meta data.', 'flexible-subscriptions' ) );
		}

		self::set_payment_meta( $subscription, $new_payment_method_meta );

		$subscription->add_order_note(
			sprintf(
				// translators: %1$s old payment method title, %2$s new payment method title
				__( 'Subscription payment method changed from %1$s to %2$s by customer.', 'flexible-subscriptions' ),
				$old_payment_method_title,
				$new_gateway->get_title()
			)
		);

		$subscription->save();

		do_action( 'woocommerce_subscription_payment_method_updated', $subscription, $new_payment_method, $old_payment_method );
		do_action( 'woocommerce_subscription_failing_payment_method_updated_' . $new_payment_method, $subscription, $new_gateway );

		return true;
	}
}
