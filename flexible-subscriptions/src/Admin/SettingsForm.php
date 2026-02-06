<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin;

use WPDesk\FlexibleSubscriptions\Admin\Settings\AccountTab;
use WPDesk\FlexibleSubscriptions\Admin\Settings\ProductsTab;
use WPDesk\FlexibleSubscriptions\Form\ExtendedForm;
use WPDesk\FlexibleSubscriptions\Form\Fields\ActionField;
use WPDesk\FlexibleSubscriptions\Form\Fields\NoOnceField;
use WPDesk\FlexibleSubscriptions\Form\Fields\Section;
use WPDesk\FlexibleSubscriptions\Form\Fields\Tab;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\InputTextField;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\SubmitField;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

/**
 * This is inspired by wp-forms, but changes the internals for better feeling.
 */
class SettingsForm extends ExtendedForm {

	private string $active_tab = 'account';

	public function __construct( string $active_tab = 'account' ) {
		$this->active_tab = $active_tab;
		parent::__construct( $this->fields(), 'fsb-settings' );
		$this->set_action( add_query_arg( [ 'tab' => $this->active_tab ], admin_url( 'admin-post.php' ) ) );
	}

	private function fields(): array {
		$tabs = apply_filters(
			'fsub/settings/tabs',
			[
				( new ProductsTab() )
					->set_name( 'product' )
					->set_label( esc_html__( 'Products', 'flexible-subscriptions' ) ),
				( new AccountTab() )
					->set_name( 'account' )
					->set_label( esc_html__( 'Account', 'flexible-subscriptions' ) ),
			]
		);

		return [
			( new NoOnceField( 'fsb_save_settings' ) )->set_name( '_wpnonce' ),
			( new ActionField() )
				->set_name( 'action' )
				->set_default_value( 'fsb_save_settings' ),
			...$tabs,
			( new SubmitField() )
					->set_label( esc_html__( 'Save changes', 'flexible-subscriptions' ) )
					->add_class( 'button-primary' )
					->set_name( 'save' ),
		];
	}

	public function tabs(): array {
		return array_filter(
			$this->get_fields(),
			static fn ( $field ) => $field instanceof Tab
		);
	}

	public function set_active_tab( string $tab ): void {
		$this->active_tab = $tab;
	}

	#[\Override]
	public function render_fields( Renderer $renderer ): string {
		$content     = '';
		$fields_data = $this->get_data();
		foreach ( $this->get_fields() as $field ) {
			if ( $field instanceof Tab && $this->active_tab !== $field->get_name() ) {
				continue;
			}
			$content .= $renderer->render(
				$field->should_override_form_template() ? $field->get_template_name() : 'form-field',
				[
					'field'         => $field,
					'renderer'      => $renderer,
					'name_prefix'   => $this->get_form_id(),
					'value'         => $this->get_field_value( $field, $fields_data ),
					'template_name' => $field->get_template_name(),
				]
			);
		}

		return $content;
	}

	public function get_current_tab_data(): array {
		foreach ( $this->get_fields() as $field ) {
			if ( $field instanceof Tab && $this->active_tab === $field->get_name() ) {
				return $this->get_data_by_field( $field, $this->updated_data );
			}
		}

		return [];
	}

	#[\Override]
	public function render_form( Renderer $renderer ): string {
		$content  = $renderer->render(
			'form-start',
			[
				'form'       => $this,
				'active_tab' => $this->active_tab,
			]
		);
		$content .= $this->render_fields( $renderer );
		$content .= $renderer->render( 'form-end' );

		return $content;
	}
}
