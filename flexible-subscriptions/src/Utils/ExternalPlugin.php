<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

class ExternalPlugin {

	private string $plugin;

	public function __construct( string $plugin ) {
		$this->plugin = $plugin;
	}

	public function get_file_name(): string {
		return $this->plugin;
	}

	public function is_active(): bool {
		return \in_array( $this->plugin, (array) get_option( 'active_plugins', [] ), true ) || $this->is_plugin_active_for_network();
	}

	private function is_plugin_active_for_network(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );
		return isset( $plugins[ $this->plugin ] );
	}
}
