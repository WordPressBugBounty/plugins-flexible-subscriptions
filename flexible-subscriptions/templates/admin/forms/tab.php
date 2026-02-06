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
<article id="<?php echo esc_attr( sprintf( '%s_%s', $name_prefix, $field->get_name() ) ); ?>">
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
</article>
