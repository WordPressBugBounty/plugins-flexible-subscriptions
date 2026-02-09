<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

use WC_Cart;

final class CartContext {

	private ?WC_Cart $global_cart = null;

	private static bool $lock = false;

	/**
	 * Promote cart to a global context safely.
	 *
	 * @template T
	 *
	 * @param WC_Cart      $cart
	 * @param callable():T $callback
	 *
	 * @return T
	 */
	public function temporarily_promote_to_global( WC_Cart $cart, callable $callback ) {
		if ( self::$lock ) {
			return $callback();
		}

		self::$lock          = true;
		$this->global_cart ??= WC()->cart;
		WC()->cart           = $cart;

		try {
			return $callback();
		} finally {
			WC()->cart  = $this->global_cart;
			self::$lock = false;
		}
	}

	public function get_real_global_cart(): WC_Cart {
		return $this->global_cart ?? WC()->cart;
	}
}
