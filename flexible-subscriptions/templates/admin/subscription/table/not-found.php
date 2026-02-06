<?php
/**
 * @var int $subscription_id
 */

defined( 'ABSPATH' ) || exit;
?>

<strong>
	<?php
	// translators: placeholder is a subscription ID.
	printf( esc_html_x( '#%s', 'hash before subscription number', 'flexible-subscriptions' ), esc_html( (string) $subscription_id ) )
	?>
</strong>
<div class="wcs-unknown-order-info-wrapper">
	<a href="#">
		<?php
		// translators: Placeholder is a <br> HTML tag.
		echo wc_help_tip( sprintf( __( "This subscription couldn't be loaded from the database. %s Click to learn more.", 'flexible-subscriptions' ), '</br>' ) );
		?>
	</a>
</div>
