<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Product;

use WPDesk\FlexibleSubscriptions\Product\SimpleSubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductVariation;
use WPDesk\FlexibleSubscriptions\Product\VariableSubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class SubscriptionProductType implements HookProvider {

	public function hooks(): void {
		add_filter( 'product_type_selector', [ $this, 'register_product_types' ] );
		add_filter( 'woocommerce_product_class', [ $this, 'register_product_class' ], 10, 2 );
		add_filter( 'woocommerce_product_class', [ $this, 'subscription_variation_class' ], 10, 4 );
	}

	/**
	 * @param array<string, string> $types
	 *
	 * @return array<string, string>
	 */
	public function register_product_types( array $types ): array {
		$types['fsb-subscription']          = esc_html__( 'Subscription', 'flexible-subscriptions' );
		$types['fsb-variable-subscription'] = esc_html__( 'Variable Subscription', 'flexible-subscriptions' );

		return $types;
	}

	public function register_product_class( string $classname, string $product_type ): string {
		switch ( $product_type ) {
			case 'fsb-subscription':
				return SimpleSubscriptionProduct::class;
			case 'fsb-variable-subscription':
				return VariableSubscriptionProduct::class;
			default:
				return $classname;
		}
	}

	public function subscription_variation_class( string $classname, string $product_type, string $post_type, int $product_id ): string {
		if ( 'product_variation' === $post_type && 'variation' === $product_type ) {
			$post = get_post( $product_id );

			if ( $post instanceof \WP_Post ) {
				$terms = get_the_terms( $post->post_parent, 'product_type' );

				$parent_product_type = ! empty( $terms ) && isset( current( $terms )->slug ) ? current( $terms )->slug : '';

				if ( 'fsb-variable-subscription' === $parent_product_type ) {
					$classname = SubscriptionProductVariation::class;
				}
			}
		}

		return $classname;
	}
}
