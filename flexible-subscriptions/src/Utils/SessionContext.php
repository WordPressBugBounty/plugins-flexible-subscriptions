<?php

namespace WPDesk\FlexibleSubscriptions\Utils;

class SessionContext {

	private array $shipping_methods;

	public function forge_session(): void {
		if ( isset( $this->shipping_methods ) ) {
			throw new \RuntimeException( 'Shipping methods already set in session.' );
		}

		$this->shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
	}

	public function get_shipping_methods() {
		return $this->shipping_methods;
	}

	public function ensure_shipping_methods_available(): void {
		if ( empty( $this->shipping_methods ) ) {
			return;
		}
		WC()->session->set( 'chosen_shipping_methods', $this->shipping_methods );
	}

	public function flush(): void {
		WC()->session->set( 'chosen_shipping_methods', $this->shipping_methods );
		unset( $this->shipping_methods );
	}
}
