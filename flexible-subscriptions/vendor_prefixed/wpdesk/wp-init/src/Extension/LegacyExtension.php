<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\EmptyDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\ReadableConfig;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
class LegacyExtension implements Extension
{
    public function build(ContainerBuilder $builder, Plugin $plugin, ReadableConfig $config): void
    {
        if (!$config->has('plugin_class_name')) {
            throw new \LogicException('To use legacy driver you must set "plugin_class_name" in your config pointing to the class name of your plugin.');
        }
        $builder->add_definitions([\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class => $this->as_plugin_info($plugin, $config)]);
    }
    private function as_plugin_info(Plugin $plugin, ReadableConfig $config): \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info
    {
        $plugin_info = new \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info();
        $plugin_info->set_plugin_file_name($plugin->get_basename());
        $plugin_info->set_plugin_name($plugin->get_name());
        $plugin_info->set_plugin_dir($plugin->get_path());
        $plugin_info->set_version($plugin->get_version());
        $plugin_info->set_text_domain($plugin->get_slug());
        $plugin_info->set_plugin_url($plugin->get_url());
        $plugin_info->set_class_name($config->get('plugin_class_name'));
        $plugin_info->set_product_id($config->get('product_id', ''));
        $plugin_info->set_plugin_shops($config->get('shops', []));
        return $plugin_info;
    }
    public function bindings(ContainerInterface $c): BindingDefinitions
    {
        return new EmptyDefinitions();
    }
}
