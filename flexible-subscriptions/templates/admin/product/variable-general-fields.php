<?php
/**
 * @var int $loop
 * @var \WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper   $product
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;
use WPDesk\FlexibleSubscriptions\Product\ProductEditTips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$period_iterator = new \WPDesk\FlexibleSubscriptions\PeriodIterator();
?>
<div class="js-fsb-variable-billing-schedule fsb-variable-fields-group show_if_fsb-variable-subscription">
	<fieldset class="form-fieldset form-row form-row-first">
		<legend><?php esc_html_e( 'Payment frequency', 'flexible-subscriptions' ); ?>
		<?php echo wc_help_tip( ProductEditTips::payment_frequency() ); ?>
		</legend>
		<div class="variable-frequency-fields">
			<span class="form-row form-info-glue"><?php echo esc_html_x( 'every', 'Prepended in product configuration inputs to form i.e. "every 3 day(s)"', 'flexible-subscriptions' ); ?></span>
	<?php
			woocommerce_wp_text_input(
				[
					'id'                => sprintf( 'fsb_variable_subscription_period_interval[%d]', esc_attr( (string) $loop ) ),
					'class'             => 'form-row',
					'label'             => __( 'Interval', 'flexible-subscriptions' ),
					'value'             => $product->get_billing_frequency()->length() ?: 1,
					'type'              => 'number',
					'wrapper_class'     => 'label_hidden',
					'custom_attributes' => [
						'min'  => '1',
					],
				]
			);

			woocommerce_wp_select(
				[
					'id'            => sprintf( 'fsb_variable_subscription_period[%d]', esc_attr( (string) $loop ) ),
					'class'         => '',
					'label'         => __( 'Period', 'flexible-subscriptions' ),
					'value'         => $product->get_billing_frequency()->unit(),
					'options'       => $period_iterator,
					'wrapper_class' => 'label_hidden',
				]
			);
			?>
		</div>
	</fieldset>
	<fieldset class="form-fieldset form-row form-row-last">
		<legend><?php esc_html_e( 'Billing cycles', 'flexible-subscriptions' ); ?>
		<?php echo wc_help_tip( ProductEditTips::expiration() ); ?>
	</legend>
		<div class="variable-frequency-fields">
	<?php
	woocommerce_wp_text_input(
		[
			'id'                => sprintf( 'fsb_variable_total_billing_cycles[%d]', esc_attr( (string) $loop ) ),
			'class'             => '',
			'label'             => __( 'Total billing cycles', 'flexible-subscriptions' ),
			'value'             => $product->can_expire() ? $product->get_total_billing_cycles() : null,
			'wrapper_class'     => 'label_hidden',
			'type'              => 'number',
			'custom_attributes' => [
				'min'  => '0',
			],
			'description'       =>
			sprintf(
			// translators: %s is the length of subscription, i.e. "3 days"
				esc_html__( 'Expire subscription after %s', 'flexible-subscriptions' ),
				sprintf(
					'<span class="js-fsb-total-cycles">%s</span>',
					$product->can_expire() ?
					WPInterval::from_spec( new SingleUnitSpec( $product->get_expiration()->length(), $product->get_billing_frequency()->unit() ) )->to_readable_string() : ''
				)
			),
		]
	);
	?>
		</div>
	</fieldset>
</div>
<div class="js-fsb-variable-subscription-trial fsb-variable-fields-group show_if_fsb-variable-subscription">
	<fieldset class="form-fieldset form-row form-row-full short">
		<legend>
			<?php esc_html_e( 'Free trial', 'flexible-subscriptions' ); ?>
		<?php echo wc_help_tip( ProductEditTips::free_trial() ); ?>
		</legend>
		<div class="variable-frequency-fields">
		<?php
			woocommerce_wp_text_input(
				[
					'id'                    => sprintf( 'fsb_variable_subscription_trial_length[%d]', esc_attr( (string) $loop ) ),
					'class'                 => '',
					'label'                 => __( 'trail length', 'flexible-subscriptions' ),
					'value'                 => $product->has_trial() ? $product->get_trial_duration()->length() : null,
					'wrapper_class'         => 'label_hidden',
					'type'                  => 'number',
					'custom_attributes'     => [
						'step' => '1',
						'min'  => '0',
					],
				]
			);

			woocommerce_wp_select(
				[
					'id'            => sprintf( 'fsb_variable_subscription_trial_period[%d]', esc_attr( (string) $loop ) ),
					'class'         => 'select',
					'label'         => __( 'trial period', 'flexible-subscriptions' ),
					'value'         => $product->has_trial() ? $product->get_trial_duration()->unit() : null,
					'options'       => $period_iterator,
					'wrapper_class' => 'label_hidden',
				]
			);
			?>
		</div>
	</fieldset>
</div>
