<?php

namespace WPDesk\FlexibleSubscriptions\Utils;

class SessionContext {

	private array $shipping_methods = [];

	public function forge_session(): void {
		if ( ! empty( $this->shipping_methods ) ) {
			throw new \RuntimeException( 'Shipping methods already set in session.' );
		}

		if ( WC()->session === null ) {
			return;
		}

		$this->shipping_methods = (array) ( WC()->session->get( 'chosen_shipping_methods' ) ?? [] );
	}

	public function get_shipping_methods() {
		return $this->shipping_methods;
	}

	public function ensure_shipping_methods_available(): void {
		if ( empty( $this->shipping_methods ) ) {
			return;
		}
		if ( WC()->session === null ) {
			return;
		}
		WC()->session->set( 'chosen_shipping_methods', $this->shipping_methods );
	}

	public function flush(): void {
		if ( WC()->session === null ) {
			return;
		}
		WC()->session->set( 'chosen_shipping_methods', $this->shipping_methods );
		$this->shipping_methods = [];
	}
}
