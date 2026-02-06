<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\ComposableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\HookableDefinition;
class HookableBinder implements ComposableBinder
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }
    public function can_bind(Definition $def): bool
    {
        return $def instanceof HookableDefinition;
    }
    public function bind(Definition $def): void
    {
        $this->container->get($def->value())->hooks();
    }
}
