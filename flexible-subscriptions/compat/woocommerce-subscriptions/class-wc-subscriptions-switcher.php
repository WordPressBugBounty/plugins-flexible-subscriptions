<?php
/**
 * Flexible Subscriptions does not support subscription switching currently.
 */
class WC_Subscriptions_Switcher {

	/**
	 * Check if the cart includes items to switch an existing subscription.
	 *
	 * @param string $item_action Types of items to include ("any", "switch", or "add"). Ignored.
	 * @return false
	 */
	public static function cart_contains_switches( $item_action = 'switch' ) {
		return false;
	}
}
