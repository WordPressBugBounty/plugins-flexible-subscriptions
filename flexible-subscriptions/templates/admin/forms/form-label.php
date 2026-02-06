<?php
/**
 * @var WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field $field
 * @var string $name_prefix
 * @var string $value
 */

defined( 'ABSPATH' ) || exit;
?>
<th class="titledesc" scope="row">
	<label for="<?php echo \esc_attr( $name_prefix ); ?>[<?php echo \esc_attr( $field->get_name() ); ?>]<?php echo \esc_attr( $field->is_multiple() ) ? '[]' : ''; ?>"><?php echo \esc_html( $field->get_label() ); ?>
		<?php if ( $field->has_description_tip() ) : ?>
			<?php echo wp_kses_post( wc_help_tip( $field->get_description_tip() ) ); ?>
		<?php endif ?>
	</label>
</th>
