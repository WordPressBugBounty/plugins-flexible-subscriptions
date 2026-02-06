<?php
declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPDesk\FlexibleSubscriptions\Subscription\CustomOrdersTableStore;
use WPDesk\FlexibleSubscriptions\Subscription\PostMetaStore;
use WPDesk\FlexibleSubscriptions\Utils;

class OverrideDataStores implements Utils\HookProvider {

	public function hooks(): void {
		add_filter( 'woocommerce_data_stores', $this );
	}

	/**
	 * @param array<string, class-string> $data_stores
	 *
	 * @return array<string, class-string>
	 */
	public function __invoke( array $data_stores ): array {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$data_stores['fsb_subscription'] = new CustomOrdersTableStore();
			$data_stores['fsb_subscription']->init(
				wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta::class ),
				wc_get_container()->get( \Automattic\WooCommerce\Internal\Utilities\DatabaseUtil::class ),
				wc_get_container()->get( \Automattic\WooCommerce\Proxies\LegacyProxy::class )
			);
		} else {
			$data_stores['fsb_subscription'] = PostMetaStore::class;
		}

		$data_stores['product-fsb-variable-subscription']  = \WC_Product_Variable_Data_Store_CPT::class;
		$data_stores['product-fsb_subscription_variation'] = \WC_Product_Variation_Data_Store_CPT::class;

		return $data_stores;
	}
}
