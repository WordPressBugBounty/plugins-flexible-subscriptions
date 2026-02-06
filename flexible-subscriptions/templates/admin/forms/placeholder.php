<?php
/**
 * @var \WPDesk\Forms\Field $field
 * @var \WPDesk\View\Renderer\Renderer $renderer
 * @var string $name_prefix
 * @var string $value
 * @var string $template_name Real field template.
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="<?php echo esc_attr( $field->get_id() ); ?>">
	<?php echo wp_kses_post( $field->get_description() ); ?>
</div>
