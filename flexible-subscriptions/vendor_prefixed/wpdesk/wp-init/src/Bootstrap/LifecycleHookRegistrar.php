<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder\CallableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder\CompositeBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder\HookableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module\ModuleCollection;
/**
 * Registers WordPress activation and deactivation hooks.
 */
final class LifecycleHookRegistrar
{
    private string $plugin_file;
    private ModuleCollection $modules;
    public function __construct(string $plugin_file, ModuleCollection $modules)
    {
        $this->plugin_file = $plugin_file;
        $this->modules = $modules;
    }
    public function register(ContainerInterface $container, BootstrapContext $context): void
    {
        $this->register_activation_hook($container, $context);
        $this->register_deactivation_hook($container, $context);
    }
    private function register_activation_hook(ContainerInterface $container, BootstrapContext $context): void
    {
        $definitions = $this->collect_activate_definitions($container, $context);
        if ($definitions === []) {
            return;
        }
        $binder = $this->lifecycle_binder($container);
        register_activation_hook($this->plugin_file, static function () use ($binder, $definitions): void {
            foreach ($definitions as $definition) {
                $binder->bind($definition);
            }
        });
    }
    private function register_deactivation_hook(ContainerInterface $container, BootstrapContext $context): void
    {
        $definitions = $this->collect_deactivate_definitions($container, $context);
        if ($definitions === []) {
            return;
        }
        $binder = $this->lifecycle_binder($container);
        register_deactivation_hook($this->plugin_file, static function () use ($binder, $definitions): void {
            foreach ($definitions as $definition) {
                $binder->bind($definition);
            }
        });
    }
    /**
     * @return Definition<mixed>[]
     */
    private function collect_activate_definitions(ContainerInterface $container, BootstrapContext $context): array
    {
        $definitions = [];
        foreach ($this->modules as $module) {
            foreach ($module->activate($container, $context)->load() as $definition) {
                $definitions[] = $definition;
            }
        }
        return $definitions;
    }
    /**
     * @return Definition<mixed>[]
     */
    private function collect_deactivate_definitions(ContainerInterface $container, BootstrapContext $context): array
    {
        $definitions = [];
        foreach ($this->modules as $module) {
            foreach ($module->deactivate($container, $context)->load() as $definition) {
                $definitions[] = $definition;
            }
        }
        return $definitions;
    }
    private function lifecycle_binder(ContainerInterface $container): CompositeBinder
    {
        return new CompositeBinder(new HookableBinder($container), new CallableBinder($container));
    }
}
