<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
final class LegacyBuilderModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        if (!class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class)) {
            throw new \LogicException('LegacyBuilderModule requires "wpdesk/wp-builder" to be installed.');
        }
        $config = $context->module_config(self::class);
        $class_name = $config['plugin_class_name'] ?? null;
        if (!is_string($class_name) || $class_name === '') {
            throw new \LogicException('LegacyBuilderModule requires "plugin_class_name" in module config.');
        }
        $plugin = $context->plugin();
        $plugin_info = new \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info();
        $plugin_info->set_plugin_file_name($plugin->get_basename());
        $plugin_info->set_plugin_name($plugin->get_name());
        $plugin_info->set_plugin_dir($plugin->get_path());
        $plugin_info->set_version($plugin->get_version());
        $plugin_info->set_text_domain($plugin->get_slug());
        $plugin_info->set_plugin_url($plugin->get_url());
        $plugin_info->set_class_name($class_name);
        $plugin_info->set_product_id(isset($config['product_id']) ? (string) $config['product_id'] : '');
        $plugin_info->set_plugin_shops(isset($config['shops']) ? (array) $config['shops'] : []);
        $builder->add_definitions([\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class => $plugin_info]);
    }
}
