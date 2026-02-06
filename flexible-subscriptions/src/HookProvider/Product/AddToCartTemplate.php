<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\Product\VariableSubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class AddToCartTemplate implements HookProvider {

	private Renderer $renderer;

	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	public function hooks(): void {
		\add_action( 'woocommerce_fsb-subscription_add_to_cart', [ $this, 'simple_subscription_button' ] );
		\add_action( 'woocommerce_fsb-variable-subscription_add_to_cart', [ $this, 'variable_subscription_button' ] );
	}

	public function simple_subscription_button(): void {
		$this->renderer->output_render( 'single-product/add-to-cart/subscription' );
	}

	public function variable_subscription_button(): void {
		global $product;

		if ( ! $product instanceof VariableSubscriptionProduct ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart-variation' );

		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

		$this->renderer->output_render(
			'single-product/add-to-cart/variable-subscription',
			[
				'available_variations' => $get_variations ? $product->get_available_variations() : false,
				'attributes'           => $product->get_variation_attributes(),
				'selected_attributes'  => $product->get_default_attributes(),
			]
		);
	}
}
