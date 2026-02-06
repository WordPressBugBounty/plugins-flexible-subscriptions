<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

class OrderItemUtil {

	/**
	 * Returns order item meta formated in HTML.
	 *
	 * @see wc_display_item_meta
	 */
	public static function get_formatted_item_meta( \WC_Order_Item $item ): string {
		return wc_display_item_meta(
			$item,
			[
				'before'    => '',
				'after'     => '',
				'separator' => '',
				'echo'      => false,
			]
		);
	}

	public static function get_product_link( \WC_Order_Item $item ): string {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return '';
		}

		$_product = $item->get_product();

		if ( ! $_product instanceof \WC_Product ) {
			return '';
		}

		return get_edit_post_link(
			$_product->is_type( 'variation' ) ? $_product->get_parent_id() : $_product->get_id()
		);
	}

	/**
	 * Provide the name of the order item. If supported, prepend the product SKU. Note, this function
	 * respects WooCommerce filter for order item name.
	 */
	public static function get_name( \WC_Order_Item $item ): string {
		$item_name = '';

		if ( $item instanceof \WC_Order_Item_Product ) {
			$_product = $item->get_product();
			if ( wc_product_sku_enabled() && $_product && $_product->get_sku() ) {
				$item_name .= $_product->get_sku() . ' - ';
			}
		}

		$item_name .= apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false );

		return $item_name;
	}
}
