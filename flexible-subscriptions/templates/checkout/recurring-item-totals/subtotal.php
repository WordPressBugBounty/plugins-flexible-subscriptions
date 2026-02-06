<?php
/**
 * Subtotal for a subscription candidate wrapped in a HTML table row.
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 */

defined( 'ABSPATH' ) || exit;
?>

<th><?php esc_html_e( 'Subtotal', 'flexible-subscriptions' ); ?></th>
<td>
	<?php echo wp_kses_post( wc_price( $candidate->get_displayed_subtotal() ) ); ?>
</td>
