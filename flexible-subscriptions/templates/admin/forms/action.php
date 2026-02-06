<?php
/** @var WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field $field */
defined( 'ABSPATH' ) || exit;
?>

<input type="hidden" name="action" value="<?php echo esc_attr( $field->get_default_value() ); ?>">
