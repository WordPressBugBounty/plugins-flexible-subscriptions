<?php // phpcs:ignoreFile WordPress.Security.NonceVerification.Missing -- Nonces are verified in attached hook parents.
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\UTCTimestamp;
use WPDesk\FlexibleSubscriptions\Product\SimpleSubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;

class SimpleSubscriptionProductSaver implements \WPDesk\FlexibleSubscriptions\Utils\HookProvider {

	public function hooks(): void {
		add_action( 'woocommerce_process_product_meta_fsb-subscription', $this );
	}

	/** @param int $post_id */
	public function __invoke( $post_id ): void {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof SimpleSubscriptionProduct ) {
			return;
		}

		$subscription_product = new SubscriptionProductWrapper( $product );

		if ( isset( $_POST['_fsb_subscription_sign_up_fee'] ) ) {
			$subscription_product->add_meta(
				'_fsb_subscription_sign_up_fee',
				wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_fsb_subscription_sign_up_fee'] ) ) )
			);
		}

		$ships_one_time = isset( $_POST['_fsb_subscription_one_time_shipping'] );

		$subscription_product->add_meta(
			'_fsb_subscription_one_time_shipping',
			$ships_one_time ? 'yes' : 'no'
		);

		$subscription_fields = [
			'_fsb_subscription_interval',
			'_fsb_subscription_period',
			'_fsb_total_billing_cycles',
			'_fsb_subscription_trial_period',
			'_fsb_subscription_trial_length',
			'_fsb_subscription_limit',
			'_fsb_subscription_one_time_shipping',
		];

		foreach ( $subscription_fields as $field_name ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$subscription_product->add_meta( $field_name, sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) );
			}
		}

		$subscription_product->save();
	}

}
