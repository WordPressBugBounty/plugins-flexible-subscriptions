<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\QueryEnhancer;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Enhance admin order queries with additional filtering methods from request.
 */
final class QueryEnhancement implements HookProvider {

	/** @var QueryEnhancer[] */
	private array $enhancers;

	/**
	 * @param QueryEnhancer[] $enhancers
	 */
	public function __construct( array $enhancers ) {
		$this->enhancers = $enhancers;
	}

	public function hooks(): void {
		// Add filter to queries on admin orders screen to filter on order type. To avoid WC overriding our query args, we need to hook on after them on 10.
		// HPOS-ready.
		add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', $this, 11 );
		add_filter(
			'request',
			function ( $q ) {
				global $typenow;

				if ( $typenow !== 'shop_order' ) {
					return $q;
				}
				return $this( $q );
			},
			11
		);
	}

	public function __invoke( $query_args ) {
		foreach ( $this->enhancers as $enhancer ) {
			$query_args = $enhancer->enhance_query( $query_args );
		}
		return $query_args;
	}
}
