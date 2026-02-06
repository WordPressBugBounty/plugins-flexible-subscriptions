<?php
/**
 * @var WPDesk\FlexibleSubscriptions\Admin\SettingsForm $form
 * @var \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer $this
 */

use WPDesk\FlexibleSubscriptions\Utils\KsesAllowList;

defined( 'ABSPATH' ) || exit;
?>

<h2><?php esc_html_e( 'Flexible Subscriptions', 'flexible-subscriptions' ); ?></h2>

<?php

echo wp_kses( $form->render_form( $this ), KsesAllowList::allowed_html() );
