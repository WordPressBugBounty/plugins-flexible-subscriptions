<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\ArrayDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\EmptyDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\FilesystemDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootGate;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util\Path;
final class ConfigModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        $services = array_map(static function (string $service) use ($context): string {
            return (string) (new Path($service))->absolute($context->plugin()->get_path());
        }, (array) $context->config()->get('services', []));
        $builder->add_definitions(...$services);
    }
    public function bindings(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        $hooks_path = $context->config()->get('hooks');
        if ($hooks_path === null) {
            return new EmptyDefinitions();
        }
        return new FilesystemDefinitions((new Path((string) $hooks_path))->absolute($context->plugin()->get_path()));
    }
    public function activate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        $activate = $context->config()->get('activate');
        if ($activate === null) {
            return new EmptyDefinitions();
        }
        return new ArrayDefinitions($this->lifecycle_bindings($activate));
    }
    public function deactivate(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        $deactivate = $context->config()->get('deactivate');
        if ($deactivate === null) {
            return new EmptyDefinitions();
        }
        return new ArrayDefinitions($this->lifecycle_bindings($deactivate));
    }
    public function gates(ContainerInterface $container, BootstrapContext $context): array
    {
        $gates = [];
        foreach ((array) $context->config()->get('gates', []) as $gate_class) {
            if (!is_string($gate_class) || $gate_class === '') {
                throw new \LogicException('Configured gates must be class-string identifiers.');
            }
            $gate = $container->get($gate_class);
            if (!$gate instanceof BootGate) {
                throw new \LogicException(sprintf('Configured gate "%s" must implement %s.', $gate_class, BootGate::class));
            }
            $gates[] = $gate;
        }
        return $gates;
    }
    /**
     * @param mixed $bindings
     *
     * @return array<int|string, mixed>
     */
    private function lifecycle_bindings($bindings): array
    {
        if (is_callable($bindings)) {
            return [['handler' => $bindings]];
        }
        if (!is_array($bindings)) {
            return [$bindings];
        }
        if (isset($bindings['handler'])) {
            return [$bindings];
        }
        return array_map(static function ($binding) {
            if (is_callable($binding)) {
                return ['handler' => $binding];
            }
            return $binding;
        }, $bindings);
    }
}
