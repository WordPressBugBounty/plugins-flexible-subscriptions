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

<p class="submit">
	<input
		<?php if ( $field->has_classes() ) : ?>
			class="<?php echo \esc_attr( $field->get_classes() ); ?>"
		<?php endif; ?>
		<?php foreach ( $field->get_attributes() as $key => $v ) : ?>
			<?php echo \esc_attr( $key ); ?>="<?php echo \esc_attr( $v ); ?>"
		<?php endforeach; ?>
		type="<?php echo \esc_attr( $field->get_type() ); ?>"
		name="<?php echo \esc_attr( $field->get_name() ); ?>"
		value="<?php echo \esc_html( $field->get_label() ); ?>"
	/>
</p>
