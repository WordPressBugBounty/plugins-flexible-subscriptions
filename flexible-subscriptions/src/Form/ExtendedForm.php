<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Form;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Persistence\ElementNotExistsException;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

/**
 * This is inspired by wp-forms, but changes the internals for handling iterable field groups.
 */
class ExtendedForm implements Form {
	use Field\Traits\HtmlAttributes;

	private string $form_id = 'form';

	protected ?array $updated_data = null;

	/** @var Field[] */
	private array $fields;

	/**
	 * @param Field[] $fields
	 */
	public function __construct( array $fields, string $form_id = 'form' ) {
		$this->fields  = $fields;
		$this->form_id = $form_id;
		$this->set_action( '' );
		$this->set_method( 'POST' );
	}

	public function is_valid(): bool {
		foreach ( $this->fields as $field ) {
			if ( ! $this->is_field_valid( $field ) ) {
				return false;
			}
		}

		return true;
	}

	private function is_field_valid( Field $field ): bool {
		if ( is_iterable( $field ) ) {
			foreach ( $field as $child ) {
				if ( ! $this->is_field_valid( $child ) ) {
					return false;
				}
			}

			return true;
		}

		$validator = $field->get_validator();
		return $validator->is_valid( $this->updated_data[ $field->get_name() ] ?? $field->get_default_value() );
	}

	public function handle_request( array $request = [] ) {
		if ( $this->updated_data === null ) {
			$this->updated_data = [];
		}

		foreach ( $this->fields as $field ) {
			$this->handle_field_from_request( $field, $request );
		}
	}

	public function handle_field_from_request( Field $field, $request ) {
		if ( is_iterable( $field ) ) {
			foreach ( $field as $child ) {
				$this->handle_field_from_request( $child, $request );
			}
			return;
		}

		$data_key = $field->get_name();
		if ( isset( $request[ $data_key ] ) ) {
			$this->updated_data[ $data_key ] = $field->get_sanitizer()->sanitize( $request[ $data_key ] );
		}
	}

	public function set_data( ContainerInterface $data ) {
		foreach ( $this->fields as $field ) {
			$this->set_data_from_field( $field, $data );
		}
	}

	private function set_data_from_field( Field $field, ContainerInterface $data ) {
		if ( is_iterable( $field ) ) {
			foreach ( $field as $child ) {
				$this->set_data_from_field( $child, $data );
			}
			return;
		}

		$data_key = $field->get_name();
		if ( $data->has( $data_key ) ) {
			try {
				$this->updated_data[ $data_key ] = $data->get( $data_key );
			} catch ( ElementNotExistsException $e ) {
				$this->updated_data[ $data_key ] = false;
			}
		}
	}

	public function get_data(): array {
		if ( empty( $this->fields ) ) {
			return [];
		}

		$data = $this->updated_data;

		foreach ( $this->fields as $field ) {
			$data = $this->get_data_by_field( $field, $data );
		}

		return $data;
	}

	protected function get_data_by_field( Field $field, $data ): array {
		if ( is_iterable( $field ) ) {
			foreach ( $field as $child ) {
				$data = array_merge( $data, $this->get_data_by_field( $child, $data ) );
			}
			return $data;
		}

		$data_key = $field->get_name();
		if ( ! isset( $data[ $data_key ] ) ) {
			$data[ $data_key ] = $field->get_default_value();
		}

		return $data;
	}

	public function render_fields( Renderer $renderer ): string {
		$content     = '';
		$fields_data = $this->get_data();
		foreach ( $this->get_fields() as $field ) {
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

	protected function get_field_value( Field $field, array $fields_data ) {
		if ( is_iterable( $field ) ) {
			$values = [];
			foreach ( $field as $child ) {
				$values[ $child->get_name() ] = $this->get_field_value( $child, $fields_data );
			}
			return $values;
		}

		return $fields_data[ $field->get_name() ] ?? $field->get_default_value();
	}

	/** Set Form action attribute. */
	public function set_action( string $action ): self {
		$this->attributes['action'] = $action;

		return $this;
	}

	public function get_action(): string {
		return $this->attributes['action'];
	}

	/** Set Form method attribute ie. GET/POST. */
	public function set_method( string $method ): self {
		$this->attributes['method'] = $method;

		return $this;
	}

	public function get_method(): string {
		return $this->attributes['method'];
	}


	public function is_submitted(): bool {
		return null !== $this->updated_data;
	}

	public function is_active(): bool {
		return true;
	}

	public function render_form( Renderer $renderer ): string {
		$content  = $renderer->render(
			'form-start',
			[ 'form' => $this ]
		);
		$content .= $this->render_fields( $renderer );
		$content .= $renderer->render( 'form-end' );

		return $content;
	}

	public function get_fields(): array {
		$fields = $this->fields;

		usort(
			$fields,
			static function ( Field $a, Field $b ) {
				return $a->get_priority() <=> $b->get_priority();
			}
		);

		return $fields;
	}

	public function get_form_id(): string {
		return $this->form_id;
	}

	public function get_normalized_data(): array {
		return $this->get_data();
	}
}
