<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Marketing;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Library\Marketing\Boxes\Assets;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\Assets as TrackerAssets;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Library\Marketing\Boxes\MarketingBoxes;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\OptInPage;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Tracker_Persistence_Consent;

class SupportPage implements HookProvider {

	private Renderer $renderer;

	private Plugin $info;

	public function __construct( Renderer $renderer, Plugin $info ) {
		$this->renderer = $renderer;
		$this->info     = $info;
	}

	public function hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 9999 );

		Assets::enqueue_assets();
		Assets::enqueue_owl_assets();
	}

	public function add_menu_page() {
		add_submenu_page(
			'flexible-subscriptions',
			esc_html__( 'Start Here', 'flexible-subscriptions' ),
			'<b style="color: #00FFC2">' . esc_html__( 'Start Here', 'flexible-subscriptions' ) . '</b>',
			'manage_options',
			'wpdesk-marketing-' . $this->info->get_slug(),
			[ $this, 'render_page_action' ],
			11
		);
	}

	public function render_page_action() {
		$local = get_locale();
		if ( $local === 'en_US' ) {
			$local = 'en';
		}

		$consent = new WPDesk_Tracker_Persistence_Consent();
		if ( $consent->is_active() ) {
			$tracking_nag = null;
		} else {
			( new TrackerAssets( $this->info->get_slug() ) )->admin_enqueue_scripts();
			$tracking_nag = new OptInPage( $this->info->get_file(), $this->info->get_slug() );
		}

		$this->renderer->output_render(
			'marketing/marketing-page',
			[
				'boxes'        => new MarketingBoxes( 'flexible-subscriptions', $local ),
				'tracking_nag' => $tracking_nag,
			]
		);
	}
}
