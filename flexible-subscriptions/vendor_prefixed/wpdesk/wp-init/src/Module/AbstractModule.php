<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\EmptyDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
/**
 * Provides no-op defaults for optional module extension points.
 */
abstract class AbstractModule implements Module
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
    }
    public function bindings(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        return new EmptyDefinitions();
    }
    public function activate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        return new EmptyDefinitions();
    }
    public function deactivate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        return new EmptyDefinitions();
    }
    public function gates(ContainerInterface $container, BootstrapContext $context): array
    {
        return [];
    }
}
