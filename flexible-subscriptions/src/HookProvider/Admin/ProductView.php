<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductVariation;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class ProductView implements HookProvider {

	/** @var Renderer */
	private $renderer;

	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		add_action(
			'woocommerce_product_options_general_product_data',
			[ $this, 'general_fields' ]
		);
		add_action(
			'woocommerce_product_options_pricing',
			fn () => $this->renderer->output_render( 'product/pricing-fields' )
		);
		add_action(
			'woocommerce_product_options_shipping_product_data',
			fn () => $this->renderer->output_render( 'product/shipping-fields' )
		);

		add_action(
			'woocommerce_variation_options_pricing',
			[ $this, 'variable_pricing_fields' ],
			10,
			3
		);
		add_action(
			'woocommerce_product_after_variable_attributes',
			[ $this, 'variable_general_fields' ],
			10,
			3
		);
	}

	public function general_fields(): void {
		global $post;

		$product = wc_get_product( $post->ID );

		// Product can have no type yet because it is draft, thus we cannot check for SubscriptionProduct.
		// if ( ! $product instanceof SubscriptionProduct ) {
		// return;
		// }

		$subscription_product = new SubscriptionProductWrapper( $product );

		$this->renderer->output_render(
			'product/general-fields',
			[ 'product' => $subscription_product ]
		);
	}

	/**
	 * @param int      $loop
	 * @param array    $_
	 * @param \WP_Post $variation
	 */
	public function variable_pricing_fields( $loop, $_, $variation ): void {
		$variation_product = wc_get_product( $variation );

		$product_wrapper = new SubscriptionProductWrapper( $variation_product );
		$this->renderer->output_render(
			'product/variable-pricing-fields',
			[
				'loop'    => $loop,
				'product' => $product_wrapper,
			]
		);
	}

	/**
	 * @param int      $loop
	 * @param array    $_
	 * @param \WP_Post $variation
	 */
	public function variable_general_fields( $loop, $_, $variation ): void {
		$variation_product = wc_get_product( $variation );

		$product_wrapper = new SubscriptionProductWrapper( $variation_product );
		$this->renderer->output_render(
			'product/variable-general-fields',
			[
				'loop'    => $loop,
				'product' => $product_wrapper,
			]
		);
	}
}
