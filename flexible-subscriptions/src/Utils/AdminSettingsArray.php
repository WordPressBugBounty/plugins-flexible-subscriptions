<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

class AdminSettingsArray extends \ArrayObject {

	public function insert_settings_after( $needle, $value ) {
		$settings     = $this->getArrayCopy();
		$new_settings = [];

		foreach ( $settings as $i => $setting ) {
			if ( ! is_numeric( $i ) ) {
				$new_settings[ $i ] = $setting;
			} else {
				$new_settings[] = $setting;
			}

			if ( $setting['id'] === $needle ) {
				$new_settings = array_merge( $new_settings, (array) $value );
			}
		}

		$this->exchangeArray( $new_settings );
	}

	public function header_insert_after( string $needle, array $value ) {
		$settings     = $this->getArrayCopy();
		$new_settings = [];
		foreach ( $settings as $key => $setting ) {
			$new_settings[ $key ] = $setting;
			if ( $key === $needle ) {
				$new_settings = array_merge( $new_settings, $value );
			}
		}

		$this->exchangeArray( $new_settings );
	}
}
