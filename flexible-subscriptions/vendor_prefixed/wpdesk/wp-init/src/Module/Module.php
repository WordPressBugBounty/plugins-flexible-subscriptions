<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootGate;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
interface Module
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void;
    public function bindings(ContainerInterface $container, BootstrapContext $context): BindingDefinitions;
    public function activate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions;
    public function deactivate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions;
    /**
     * @return BootGate[]
     */
    public function gates(ContainerInterface $container, BootstrapContext $context): array;
}
