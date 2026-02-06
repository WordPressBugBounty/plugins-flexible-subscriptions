<?php
/**
 * @var WPDesk\FlexibleSubscriptions\Admin\SettingsForm $form
 * @var string $active_tab
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="wrap woocommerce" method="<?php echo \esc_attr( $form->get_method() ); ?>" action="<?php echo \esc_attr( $form->get_action() ); ?>">
	<nav class="nav-tab-wrapper">
		<?php foreach ( $form->tabs() as $tab ) { ?>
		<a class="nav-tab <?php echo $active_tab === $tab->get_name() ? esc_attr( 'nav-tab-active' ) : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $tab->get_name(), menu_page_url( 'fsb-settings', false ) ) ); ?>">
				<?php echo esc_html( $tab->get_label() ); ?>
			</a>
		<?php } ?>
	</nav>
	<h2 style="display:none;"></h2><?php // All admin notices will be moved here by WP js. ?>
