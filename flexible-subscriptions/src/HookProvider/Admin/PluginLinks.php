<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;

class PluginLinks implements HookProvider {

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function hooks(): void {
		add_filter( 'plugin_action_links_' . $this->plugin->get_basename(), $this );
	}

	/**
	 * @param array<int, string> $links
	 *
	 * @return array<int, string>
	 */
	public function __invoke( $links ) {
		$locale     = get_user_locale();
		$add_on_url = $locale === 'pl_PL' ? 'https://www.wpdesk.pl/sk/flexible-subscriptions-addon-pl' : 'https://wpdesk.net/sk/flexible-subscriptions-addon-en';
		return array_merge(
			[
				'<a style="font-weight: bold; color: #007050" href="' . menu_page_url( 'wpdesk-marketing-flexible-subscriptions', false ) . '">' . \esc_html__( 'Start here', 'flexible-subscriptions' ) . '</a>',
				'<a href="' . menu_page_url( 'fsb-settings', false ) . '">' . \esc_html__( 'Settings', 'flexible-subscriptions' ) . '</a>',
				'<a target="_blank" href="' . __( 'https://wpdesk.net/sk/flexible-subscriptions-docs-en', 'flexible-subscriptions' ) . '">' . \esc_html__( 'Docs', 'flexible-subscriptions' ) . '</a>',
				'<a target="_blank" href="https://wordpress.org/support/plugin/flexible-subscriptions/">' . \esc_html__( 'Support', 'flexible-subscriptions' ) . '</a>',
				'<a style="font-weight: bold; color: #FF9743" target="_blank" href="' . $add_on_url . '">' . \esc_html__( 'Add-on', 'flexible-subscriptions' ) . ' &rarr;</a>',
			],
			$links
		);
	}
}
