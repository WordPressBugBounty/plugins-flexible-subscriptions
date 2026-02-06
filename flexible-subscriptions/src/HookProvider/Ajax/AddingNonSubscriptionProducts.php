<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Ajax;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * @todo possibly this should also disable stock management for subscription level. Any stock values
 * should be decreased only on an order level, as subscription is merely a container or template for
 * creating orders.
 */
final class AddingNonSubscriptionProducts implements HookProvider {
	public function hooks(): void {
		add_filter( 'woocommerce_ajax_add_order_item_validation', $this, 10, 4 );
	}

	/**
	 * @param \WP_Error $validation_error
	 * @param \WC_Product $product
	 * @param \WC_Order $order
	 * @param int $qty
	 *
	 * @return \WP_Error
	 */
	public function __invoke( $validation_error, $product, $order, $qty ) {
		if ( $order instanceof Subscription && ! $product instanceof SubscriptionProduct ) {
			$validation_error->add( 'fsb_non_subscription_product', __( 'This product is not a type of subscription product and therefore cannot be added to a subscription.', 'flexible-subscriptions' ) );
		}
		return $validation_error;
	}
}
