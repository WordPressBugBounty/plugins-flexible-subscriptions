<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 * @var string $name_prefix
 * @var string|array $value
 * @var string $template_name
 * @var \WPDesk\FlexibleSubscriptions\Form\Fields\Section $field
 */

defined( 'ABSPATH' ) || exit;
?>
<h3><?php echo wp_kses_post( $field->get_label() ); ?></h3>
<p><?php echo wp_kses_post( $field->get_description() ); ?></p>

<table class="form-table">
	<tbody>
		<?php
		foreach ( $field->get_fields() as $inner_field ) {
			$this->output_render(
				$inner_field->should_override_form_template() ? $inner_field->get_template_name() : 'form-field',
				[
					'field'         => $inner_field,
					'renderer'      => $this,
					'name_prefix'   => $name_prefix,
					'template_name' => $inner_field->get_template_name(),
					'value'         => $value[ $inner_field->get_name() ],
				]
			);
		}
		?>
	</tbody>
</table>
