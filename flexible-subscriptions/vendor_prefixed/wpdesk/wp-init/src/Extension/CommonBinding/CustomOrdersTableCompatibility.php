<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
class CustomOrdersTableCompatibility implements Hookable
{
    private Plugin $plugin;
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }
    public function hooks(): void
    {
        add_action('before_woocommerce_init', $this);
    }
    public function __invoke(): void
    {
        // Concatenate string to make sure, prefixer will not parse it.
        $features_util_class = '\\' . 'Automattic' . '\\' . 'WooCommerce' . '\\' . 'Utilities' . '\\' . 'FeaturesUtil';
        //phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found
        if (class_exists($features_util_class)) {
            $features_util_class::declare_compatibility('custom_order_tables', $this->plugin->get_basename(), \true);
        }
    }
}
