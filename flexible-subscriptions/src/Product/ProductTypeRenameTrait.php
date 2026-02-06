<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\Product;

trait ProductTypeRenameTrait {

	/**
	 * To treat own products as the ones declared by WCS, map appropiate types. Especially useful in
	 * {@see \WC_Product::is_type()} context.
	 *
	 * @param string|string[] $type
	 *
	 * @return string[]
	 */
	private function rename_type( $type ) {
		$rename = [
			'subscription'           => 'fsb-subscription',
			'subscription_variation' => 'fsb_subscription_variation',
			'variable-subscription'  => 'fsb-variable-subscription',
		];

		return array_map( fn( $t ) => $rename[ $t ] ?? $t, (array) $type );
	}
}
