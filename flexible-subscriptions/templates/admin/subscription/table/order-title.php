<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription $the_subscription
 */

defined( 'ABSPATH' ) || exit;

$column_content = '';
$customer_tip   = '';

if ( $address = $the_subscription->get_formatted_billing_address() ) {
	$customer_tip .= _x( 'Billing:', 'meaning billing address', 'flexible-subscriptions' ) . ' ' . esc_html( $address );
}

if ( $the_subscription->get_billing_email() ) {
	// translators: placeholder is customer's billing email.
	$customer_tip .= '<br/><br/>' . sprintf( __( 'Email: %s', 'flexible-subscriptions' ), esc_attr( $the_subscription->get_billing_email() ) );
}

if ( $the_subscription->get_billing_phone() ) {
	// translators: placeholder is customer's billing phone number.
	$customer_tip .= '<br/><br/>' . sprintf( __( 'Tel: %s', 'flexible-subscriptions' ), esc_html( $the_subscription->get_billing_phone() ) );
}

if ( ! empty( $customer_tip ) ) {
	echo '<div class="tips" data-tip="' . wp_kses_post( wc_sanitize_tooltip( $customer_tip ) ) . '">';
}

$username = '';

if ( $user_info = $the_subscription->get_user() ) {

	$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

	if ( $the_subscription->get_billing_first_name() || $the_subscription->get_billing_last_name() ) {
		$username .= esc_html( ucfirst( $the_subscription->get_billing_first_name() ) . ' ' . ucfirst( $the_subscription->get_billing_last_name() ) );
	} elseif ( $user_info->first_name || $user_info->last_name ) {
		$username .= esc_html( ucfirst( $user_info->first_name ) . ' ' . ucfirst( $user_info->last_name ) );
	} else {
		$username .= esc_html( ucfirst( $user_info->display_name ) );
	}

	$username .= '</a>';

} elseif ( $the_subscription->get_billing_first_name() || $the_subscription->get_billing_last_name() ) {
	$username = trim( $the_subscription->get_billing_first_name() . ' ' . $the_subscription->get_billing_last_name() );
}
?>
<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $the_subscription->get_id() ) . '&action=edit' ) ); ?>">
	<strong><?php echo esc_html( '#' . $the_subscription->get_order_number() ); ?></strong>
</a>
<?php
echo ' '; // Don't include whitespace in translatable output.
// translators: %s is user's name.
printf( esc_html_x( 'for %s', 'Subscription title on admin table. Composes to "#123 for Jane Doe"', 'flexible-subscriptions' ), wp_kses_post( $username ) );
?>
</div>

<button type="button" class="toggle-row">
	<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'flexible-subscriptions' ); ?></span>
</button>
