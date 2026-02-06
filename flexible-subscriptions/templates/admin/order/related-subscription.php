<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Subscription\Subscription[] $subscriptions
 */
?>
<p class="form-field form-field-wide">
	<?php echo esc_html( _n( 'Related subscription: ', 'Related subscriptions: ', count( $subscriptions ), 'flexible-subscriptions' ) ); ?>
	<?php foreach ( $subscriptions as $i => $subscription ) { ?>
		<a href="<?php echo esc_url( $subscription->get_edit_order_url() ); ?>">
			<?php
			// translators: placeholder is an order number.
			printf( esc_html__( '#%1$s', 'flexible-subscriptions' ), esc_html( $subscription->get_order_number() ) );
			?>
		</a>

		<?php if ( isset( $subscriptions[ $i + 1 ] ) ) { ?>
			<span class="sep">, </span>
			<?php
		}
	}
	?>
</p>
