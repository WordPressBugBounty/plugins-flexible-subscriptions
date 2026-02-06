<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * There are possibilities, when subscription product cannot be added to
 * the cart, like when the payment gateways doesn't support multiple
 * subscriptions and the client tries to add more than one.
 *
 * If that happens, remove the subscriptions from the cart with proper notice.
 */
class ValidateNewItem implements HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_add_to_cart_validation', $this, 10, 5 );
	}

	/**
	 * @param bool $valid
	 * @param int $product_id
	 * @param int $quantity
	 * @param int $variation_id
	 * @param array<string, mixed> $variations
	 *
	 * @return bool
	 */
	public function __invoke( $valid, $product_id, $quantity, $variation_id = 0, $variations = [] ) {
		return $valid;
	}
}
