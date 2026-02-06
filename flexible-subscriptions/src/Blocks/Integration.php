<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use WPDesk\FlexibleSubscriptions\HookProvider\Product\ChangeAddButtonText;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;

class Integration implements IntegrationInterface {

	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @var ChangeAddButtonText
	 */
	private $button_text_provider;

	public function __construct( Plugin $plugin, ChangeAddButtonText $button_text_provider ) {
		$this->plugin               = $plugin;
		$this->button_text_provider = $button_text_provider;
	}

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'flexible-subscriptions';
	}

	/**
	 * When called, initializes the integration.
	 */
	public function initialize(): void {
		$script_path       = 'dist/index.js';
		$style_path        = 'dist/index.css';
		$script_asset_path = $this->plugin->get_path( 'assets/dist/index.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->plugin->get_version(),
			];

		wp_register_script(
			'fsb-blocks-integration',
			$this->plugin->get_url( 'assets/dist/index.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_register_style(
			'fsb-blocks-integration',
			$this->plugin->get_url( 'assets/dist/style-index.css' ),
			[],
			$script_asset['version']
		);

		wp_set_script_translations(
			'fsb-blocks-integration',
			'flexible-subscriptions',
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles(): array {
		return [ 'fsb-blocks-integration' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles(): array {
		return [ 'fsb-blocks-integration' ];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data(): array {
		return [
			'place_order_override' => $this->button_text_provider->__invoke( null ),
		];
	}
}
