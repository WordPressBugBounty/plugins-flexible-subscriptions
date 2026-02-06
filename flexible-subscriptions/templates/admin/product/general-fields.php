<?php
/**
 * @var \WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper $product
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\SingleUnitSpec;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;
use WPDesk\FlexibleSubscriptions\Product\ProductEditTips;

defined( 'ABSPATH' ) || exit;

$period_iterator = new \WPDesk\FlexibleSubscriptions\PeriodIterator();
?>
<div class="options_group js-fsb-billing-schedule fsb-fields-group show_if_fsb-subscription hidden">
	<p><strong><?php esc_html_e( 'Billing schedule', 'flexible-subscriptions' ); ?></strong></p>
	<fieldset class="form-field form-fieldset">
		<legend><?php esc_html_e( 'Payment frequency', 'flexible-subscriptions' ); ?></legend>
		<div class="product-frequency-fields short">
			<span class="form-info-glue"><?php echo esc_html_x( 'every', 'Prepended in product configuration inputs to form i.e. "every 3 day(s)"', 'flexible-subscriptions' ); ?></span>
	<?php
			woocommerce_wp_text_input(
				[
					'id'                => '_fsb_subscription_interval',
					'class'             => '',
					'label'             => __( 'Interval', 'flexible-subscriptions' ),
					'type'              => 'number',
					'wrapper_class'     => 'label_hidden',
					'custom_attributes' => [
						'min'  => '1',
					],
				]
			);

			woocommerce_wp_select(
				[
					'id'            => '_fsb_subscription_period',
					'class'         => 'select short',
					'label'         => __( 'Period', 'flexible-subscriptions' ),
					'options'       => $period_iterator,
					'wrapper_class' => 'label_hidden',
				]
			);
			?>
		</div>
		<?php echo wc_help_tip( ProductEditTips::payment_frequency() ); ?>
	</fieldset>
	<fieldset class="form-field form-fieldset">
		<legend><?php esc_html_e( 'Billing cycles', 'flexible-subscriptions' ); ?></legend>
		<div class="product-frequency-fields short">
	<?php
	woocommerce_wp_text_input(
		[
			'id'                => '_fsb_total_billing_cycles',
			'class'             => '',
			'label'             => __( 'Total billing cycles', 'flexible-subscriptions' ),
			'wrapper_class'     => 'label_hidden',
			'type'              => 'number',
			'custom_attributes' => [
				'min'  => '0',
			],
			'description'       =>
			'<span class="js-hidden">' . sprintf(
			// translators: %s is the length of subscription, i.e. "3 days"
				esc_html__( 'Expire subscription after %s', 'flexible-subscriptions' ),
				sprintf(
					'<span class="js-fsb-total-cycles">%s</span>',
					$product->can_expire() ?
					WPInterval::from_spec( new SingleUnitSpec( $product->get_expiration()->length(), $product->get_billing_frequency()->unit() ) )->to_readable_string() : ''
				)
			) . '</span>',
		]
	);
	?>
		</div>
		<?php echo wc_help_tip( ProductEditTips::expiration() ); ?>
	</fieldset>
</div>

<div class="options_group js-fsb-extra-fields fsb-fields-group show_if_fsb-subscription hidden">
	<fieldset class="form-field form-fieldset">
		<legend><?php esc_html_e( 'Free trial', 'flexible-subscriptions' ); ?></legend>
		<div class="product-frequency-fields short">
		<?php
			woocommerce_wp_text_input(
				[
					'id'                    => '_fsb_subscription_trial_length',
					'class'                 => '',
					'label'                 => __( 'trail length', 'flexible-subscriptions' ),
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
					'id'            => '_fsb_subscription_trial_period',
					'label'         => __( 'trial period', 'flexible-subscriptions' ),
					'options'       => $period_iterator,
					'wrapper_class' => 'label_hidden',
				]
			);
			?>
		</div>
		<?php echo wc_help_tip( ProductEditTips::free_trial() ); ?>
	</fieldset>
</div>
