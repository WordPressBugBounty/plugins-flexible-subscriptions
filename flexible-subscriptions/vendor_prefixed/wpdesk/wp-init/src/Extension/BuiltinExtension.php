<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\FilesystemDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\ReadableConfig;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
class BuiltinExtension implements Extension
{
    public function bindings(ContainerInterface $c): BindingDefinitions
    {
        return new FilesystemDefinitions(__DIR__ . '/../Resources/bindings');
    }
    public function build(ContainerBuilder $builder, Plugin $plugin, ReadableConfig $config): void
    {
        $builder->add_definitions(__DIR__ . '/../Resources/services.inc.php');
    }
}
