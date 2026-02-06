<?php // phpcs:ignoreFile WordPress.Security.NonceVerification.Missing -- Nonces are verified in attached hook parents.
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductVariation;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Product\VariableSubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class VariableSubscriptionProductSaver implements HookProvider {

	public function hooks(): void {
		add_action(
			'woocommerce_process_product_meta_fsb-variable-subscription',
			[ $this, 'process_variable_subscription_meta' ]
		);
		add_action(
			'woocommerce_save_product_variation',
			[ $this, 'save_product_variation' ],
			20,
			2
		);
	}

	/** @param int $post_id */
	public function process_variable_subscription_meta( $post_id ): void {
		$product = wc_get_product( $post_id );

		if ( ! $product instanceof VariableSubscriptionProduct ) {
			return;
		}

		update_post_meta( $post_id, '_fsb_subscription_one_time_shipping', stripslashes( isset( $_REQUEST['_fsb_subscription_one_time_shipping'] ) ? 'yes' : 'no' ) );

		// Sync the min variation price.
		\WC_Product_Variable::sync( $post_id );
	}

	public function save_product_variation( int $variation_id, int $index ): void {
		if ( is_ajax() ) {
			check_ajax_referer( 'save-variations', 'security' );

			// Check permissions again and make sure we have what we need.
			if ( ! current_user_can( 'edit_products' ) ) {
				wp_die( -1 );
			}
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation instanceof SubscriptionProductVariation ) {
			return;
		}

		$subscription_product = new SubscriptionProductWrapper( $variation );

		if ( isset( $_POST['fsb_variable_subscription_sign_up_fee'][ $index ] ) ) {
			$subscription_sign_up_fee = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['fsb_variable_subscription_sign_up_fee'][ $index ] ) ) );
			$subscription_product->set_singup_fee( $subscription_sign_up_fee );
		}

		$subscription_fields = [
			'_subscription_period'              => '_fsb_subscription_period',
			'_subscription_period_interval'     => '_fsb_subscription_interval',
			'_subscription_length'              => '_fsb_subscription_length',
			'_total_billing_cycles'             => '_fsb_total_billing_cycles',
			'_subscription_trial_period'        => '_fsb_subscription_trial_period',
			'_subscription_trial_length'        => '_fsb_subscription_trial_length',
		];

		foreach ( $subscription_fields as $request_key => $field_name ) {
			if ( isset( $_POST[ 'fsb_variable' . $request_key ][ $index ] ) ) {
				$subscription_product->add_meta(
					$field_name,
					wc_clean( wp_unslash( $_POST[ 'fsb_variable' . $request_key ][ $index ] ) )
				);
			}
		}

		$subscription_product->save();
	}
}
