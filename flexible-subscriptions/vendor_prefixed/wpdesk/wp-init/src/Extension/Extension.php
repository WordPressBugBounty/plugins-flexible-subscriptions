<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\ReadableConfig;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
interface Extension
{
    public function build(ContainerBuilder $builder, Plugin $plugin, ReadableConfig $config): void;
    public function bindings(ContainerInterface $c): BindingDefinitions;
}
