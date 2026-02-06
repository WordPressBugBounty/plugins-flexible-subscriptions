<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer;

final class OrderSubtypeEnhancer implements QueryEnhancer {

	/** @var OrderSubtype\SubtypeEnhancer[] */
	private array $enhancers;

	/** @param OrderSubtype\SubtypeEnhancer[] $enhancers */
	public function __construct( array $enhancers ) {
		$this->enhancers = $enhancers;
	}

	public function enhance_query( $query ): array {
		$selected_shop_order_subtype = isset( $_GET['shop_order_subtype'] ) ? wc_clean( wp_unslash( $_GET['shop_order_subtype'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $selected_shop_order_subtype ) ) {
			return $query;
		}

		foreach ( $this->enhancers as $enhancer ) {
			if ( $enhancer->is_needed( $selected_shop_order_subtype ) ) {
				return $enhancer->enhance( $query, $selected_shop_order_subtype );
			}
		}

		return $query;
	}
}
