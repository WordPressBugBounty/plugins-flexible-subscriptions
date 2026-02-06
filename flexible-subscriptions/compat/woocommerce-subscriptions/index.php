<?php
/**
 * Forget about any rules, when you are here. All what matters is to remain compatible with
 * WooCommerce Subscriptions as close as possible while providing own implementation to their API
 * functions.
 *
 * Do not go gentle into that good night.
 *
 * @var WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface $__fsb_compatibility_container
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/class-wc-subscription.php';
require __DIR__ . '/class-wc-subscriptions-admin.php';
require __DIR__ . '/class-wc-subscriptions-cart.php';
require __DIR__ . '/class-wc-subscriptions-change-payment-gateway.php';
require __DIR__ . '/class-wc-subscriptions-manager.php';
require __DIR__ . '/class-wc-subscriptions-product.php';
require __DIR__ . '/class-wc-subscriptions-switcher.php';
require __DIR__ . '/class-wc-subscriptions-synchroniser.php';
require __DIR__ . '/class-wc-subscriptions.php';
require __DIR__ . '/class-wcs-early-renewal-manager.php';
require __DIR__ . '/class-wcs-retry-manager.php';
require __DIR__ . '/class-wcs-retry-store.php';
require __DIR__ . '/wcs-compatibility-functions.php';
require __DIR__ . '/wcs-functions.php';
require __DIR__ . '/wcs-order-functions.php';
require __DIR__ . '/wcs-renewal-functions.php';
require __DIR__ . '/wcs-resubscribe-functions.php';
require __DIR__ . '/wcs-switch-functions.php';
require __DIR__ . '/wcs-time-functions.php';
require __DIR__ . '/wcs-user-functions.php';

$fsb_subscription_candidates = $__fsb_compatibility_container->get( \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList::class );

if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
	WC_Subscriptions_Cart::initialize( $fsb_subscription_candidates );
}

/**
 * Methods strictly required to remain compatibility:
 * - wcs_cart_contains_renewal
 * - wcs_get_subscriptions_for_order
 * - wcs_get_subscriptions_for_renewal_order
 * - wcs_is_subscription
 * - wcs_order_contains_renewal
 * - wcs_order_contains_subscription
 * - wcs_user_has_subscription
 * - wcs_get_objects_property (payu)
 * - wcs_is_manual_renewal_enabled (paypal)
 * - WC_Subscription::get_billing_interval
 * - WC_Subscription::get_billing_period
 * - WC_Subscription::get_last_order
 * - WC_Subscription::get_time
 * - WC_Subscription::has_status
 * - WC_Subscription::payment_complete
 * - WC_Subscription::payment_complete_for_order (payu)
 * - WC_Subscription::payment_failed
 * - WC_Subscription::update_status
 * - WC_Susbcription::get_payment_method_to_display
 * - WC_Susbcription::get_related_orders
 * - WC_Subscriptions::$version (stripe)
 * - WC_Subscriptions_Cart::cart_contains_subscription
 * - WC_Subscriptions_Change_Payment_Gateway::update_payment_method (stripe)
 * - WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment (pymntpl-paypal-woocommerce)
 * - WC_Subscriptions_Product::get_sign_up_fee (stripe)
 * - WC_Subscriptions_Product::get_trial_length (stripe, paypal)
 * - WC_Subscriptions_Product::is_subscription (stripe, paypal)
 * - WC_Subscriptions_Renewal_Order::get_renewal_orders
 * - WCS_Retry_Manager::store (payu)
 * - WCS_Retry_Store::get_last_retry_for_order (payu)
 * - WC_Subscriptions_Admin::$option_prefix (mollie, paypal)
 * - WC_Subscriptions_Manager::activate_subscriptions_for_order (mollie)
 * - WC_Subscriptions_Switcher::cart_contains_switches (stripe)
 * - WC_Subscriptions_Synchroniser::calculate_first_payment_date (paypal)
 * - WC_Subscriptions_Synchroniser::is_payment_upfront (paypal)
 * - WC_Subscriptions_Synchroniser::is_product_synced (paypal)
 * - WC_Subscriptions_Synchroniser::is_today (paypal)
 *
 * Commonly used filters and actions:
 * - wcs_default_retry_rules (payu, p24)
 * - wcs_get_retry_rule_raw (stripe)
 * - wcs_is_scheduled_payment_attempt (payu)
 * - wcs_renewal_order_created (stripe, mollie)
 * - wcs_resubscribe_order_created (stripe, mollie)
 * - woocommerce_my_subscriptions_payment_method (stripe, p24, payu)
 * - woocommerce_scheduled_subscription_payment_{gateway_id}
 * - woocommerce_subscription_failing_payment_method_updated_{gateway_id}
 * - woocommerce_subscription_payment_meta (stripe, mollie)
 * - woocommerce_subscription_validate_payment_meta (stripe, mollie)
 * - woocommerce_subscriptions_change_payment_before_submit (stripe)
 * - wcs_resubscribe_order_created (mollie)
 * - wcs_view_subscription_actions (paypal)
 * - woocommerce_customer_changed_subscription_to_active (paypal)
 * - woocommerce_customer_changed_subscription_to_cancelled (paypal)
 * - woocommerce_process_shop_subscription_meta (paypal)
 * - woocommerce_subscription_before_actions (paypal)
 * - woocommerce_subscription_payment_complete (paypal)
 * - woocommerce_subscription_payment_method_to_display (paypal)
 *
 * Notes on particular usage:
 * - WooCommerce Stripe Gateway uses WC_Product::get_type to check product type (instead of is_type). If a product is literal 'subscription', it tries to add sign-up fee. We may possibly walk around this by handling sign-up fee ourself.
 */
