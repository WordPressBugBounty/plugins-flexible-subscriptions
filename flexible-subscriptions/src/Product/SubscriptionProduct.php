<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

/**
 * Empty interface to mark common subscription products across Flexible Subscriptions. We rely on
 * interface, because this seems to be a better way to check if a product is a subscription product
 * within our plugin. Relying on product type or other custom property may be hard to determine,
 * if product actually applies for our rules.
 *
 * As long as implementing interface relies only on convention, we hope it will be used consistently
 * across the plugin.
 *
 * This interface MUST only be implemented by classes that extend WC_Product.
 */
interface SubscriptionProduct {

}
