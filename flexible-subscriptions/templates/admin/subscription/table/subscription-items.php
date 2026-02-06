<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 * @var \WC_Order_Item_Product[] $subscription_items
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $the_subscription
 */

defined( 'ABSPATH' ) || exit;

if ( count( $subscription_items ) === 1 ) {
	$this->output_render( 'subscription/table/single-subscription-item', [ 'subscription_item' => array_values( $subscription_items )[0] ] );
} else {
	$this->output_render(
		'subscription/table/subscription-items-table',
		[
			'subscription_items' => $subscription_items,
			'the_subscription'   => $the_subscription,
		]
	);
}
