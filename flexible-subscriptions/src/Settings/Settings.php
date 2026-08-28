<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Settings;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;

class Settings implements ContainerInterface {
	public function get( $key, $default = null ) {
		return get_option( $key, $default );
	}

	/**
	 * Only check if option exists (may be empty).
	 */
	public function has( $key ): bool {
		return get_option( $key ) !== false;
	}

	public function set( string $key, $value ) {
		return update_option( $key, $value );
	}

	public function get_bool( string $key, $default = false ) {
		return filter_var( $this->get( $key, $default ), \FILTER_VALIDATE_BOOLEAN );
	}
}
