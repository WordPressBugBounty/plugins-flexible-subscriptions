<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\ComposableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\CallableDefinition;
final class CallableBinder implements ComposableBinder
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }
    public function can_bind(Definition $def): bool
    {
        return $def instanceof CallableDefinition;
    }
    public function bind(Definition $def): void
    {
        $ref = new \ReflectionFunction($def->value());
        $parameters = [];
        foreach ($ref->getParameters() as $ref_param) {
            $parameters[] = $this->container->get($ref_param->getType()->getName());
        }
        $ref->invokeArgs($parameters);
    }
}
