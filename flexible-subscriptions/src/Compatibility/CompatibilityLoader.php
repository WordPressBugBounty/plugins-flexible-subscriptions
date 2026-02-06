<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Compatibility;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;

class CompatibilityLoader {

	private Plugin $plugin;

	private ContainerInterface $container;

	public function __construct( Plugin $plugin, ContainerInterface $container ) {
		$this->plugin    = $plugin;
		$this->container = $container;
	}

	public function load( string $plugin ): void {
		if ( $plugin === 'woocommerce-subscriptions' ) {
			$compat_path = $this->plugin->get_path( "/compat/$plugin/index.php" );
			if ( file_exists( $compat_path ) ) {
				$__fsb_compatibility_container = $this->container;
				require_once $compat_path;
			}
			return;
		}

		throw new \RuntimeException( "Couldn't find compatibility layer for $plugin" );
	}
}
