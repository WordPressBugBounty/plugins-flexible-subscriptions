<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Blocks;

use WPDesk\FlexibleSubscriptions\Blocks\Integration;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class BlocksIntegrationLoader implements HookProvider {

	private Integration $integration;


	public function __construct( Integration $integration ) {
		$this->integration = $integration;
	}

	public function hooks(): void {
		add_action( 'woocommerce_blocks_loaded', $this );
	}

	public function __invoke(): void {
		foreach ( [ 'cart', 'checkout', 'mini-cart' ] as $block_name ) {
			add_action(
				"woocommerce_blocks_{$block_name}_block_registration",
				function ( $integration_registry ) {
					$integration_registry->register( $this->integration );
				}
			);
		}
	}
}
