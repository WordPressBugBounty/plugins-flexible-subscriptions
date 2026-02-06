<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\ArrayDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\FilesystemDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\Configuration;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\ReadableConfig;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util\Path;
class ConfigExtension implements Extension
{
    public function bindings(ContainerInterface $c): BindingDefinitions
    {
        $config = $c->get(Configuration::class);
        if ($config->has('hook_resources_path')) {
            return new FilesystemDefinitions((new Path($config->get('hook_resources_path')))->absolute($c->get(Plugin::class)->get_path()));
        }
        return new ArrayDefinitions([]);
    }
    public function build(ContainerBuilder $builder, Plugin $plugin, ReadableConfig $config): void
    {
        $services = array_map(fn(string $service) => (string) (new Path($service))->absolute($plugin->get_path()), (array) $config->get('services', []));
        $builder->add_definitions(...$services);
    }
}
