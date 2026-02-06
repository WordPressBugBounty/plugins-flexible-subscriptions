<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Admin\SettingsForm;
use WPDesk\FlexibleSubscriptions\Settings\Settings;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class SettingsPage implements HookProvider {

	private Renderer $renderer;

	private Settings $settings;

	public function __construct( Settings $settings, Renderer $renderer ) {
		$this->renderer = $renderer;
		$this->settings = $settings;
	}

	public function hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 9999 );
		add_action( 'admin_post_fsb_save_settings', [ $this, 'save_settings' ] );
	}

	public function add_menu_page(): void {
		add_submenu_page(
			'flexible-subscriptions',
			esc_html__( 'Flexible Subscriptions settings', 'flexible-subscriptions' ),
			esc_html__( 'Settings', 'flexible-subscriptions' ),
			'manage_options',
			'fsb-settings',
			[ $this, 'render' ],
			10
		);
	}

	public function render(): void {
		$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'account' ) );
		$form       = new SettingsForm( $active_tab );
		$form->set_data( $this->settings );
		$this->renderer->output_render(
			'settings',
			[ 'form' => $form ]
		);
	}

	public function save_settings(): void {
		$form = new SettingsForm();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified during $form::is_valid() call, before any values will actually be processed.
		$form_id = $form->get_form_id();
		if ( ! isset( $_POST[ $form_id ] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		if ( empty( $_POST[ $form_id ]['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $form_id ]['_wpnonce'] ) ), 'fsb_save_settings' ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$form->set_active_tab( sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ) );

		$sanitized_values = array_map(
			$sanitize     = fn ( $v ) => is_scalar( $v ) ? sanitize_text_field( $v ) : $sanitize( $v ), // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			is_array( $_POST[ $form_id ] ) ? wp_unslash( $_POST[ $form_id ] ) : [] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		);
		$form->handle_request( $sanitized_values );
		if ( $form->is_submitted() && $form->is_valid() ) {
			foreach ( $form->get_current_tab_data() as $key => $value ) {
				$this->settings->set( $key, $value );
			}
		}
		// phpcs:enable

		wp_safe_redirect( wp_get_referer() );
		exit;
	}
}
