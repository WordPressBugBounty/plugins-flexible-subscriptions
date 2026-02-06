<?php
/**
 * Fees for a subscription candidate wrapped in a HTML table row.
 *
 * @var \WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate $candidate
 */

defined( 'ABSPATH' ) || exit;
?>

<th>
	<?php
	foreach ( $candidate->get_fees() as $fee ) {
		echo wp_kses_post( wpautop( $fee->name ) );
	}
	?>
</th>
<td>
	<?php
	foreach ( $candidate->get_fees() as $fee ) {
		wc_cart_totals_fee_html( $fee );
	}
	?>
</td>
