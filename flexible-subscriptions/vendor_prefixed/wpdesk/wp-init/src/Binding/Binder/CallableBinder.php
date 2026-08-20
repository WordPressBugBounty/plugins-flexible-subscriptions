<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\ComposableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\CallableDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Exception\InvalidCallableBinding;
/**
 * @internal Binding implementation detail.
 */
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
    /** @param Definition<mixed> $def */
    public function bind(Definition $def): void
    {
        if (!$def instanceof CallableDefinition) {
            throw new InvalidCallableBinding(sprintf('Expected %s binding definition.', CallableDefinition::class));
        }
        $callable = $this->normalize_callable($def->value());
        $ref = new \ReflectionFunction($callable);
        $parameters = [];
        foreach ($ref->getParameters() as $ref_param) {
            $parameters[] = $this->resolve_parameter($ref_param);
        }
        $ref->invokeArgs($parameters);
    }
    private function normalize_callable(callable $callable): \Closure
    {
        return \Closure::fromCallable($callable);
    }
    /** @return mixed */
    private function resolve_parameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if (!$type instanceof \ReflectionNamedType) {
            throw new InvalidCallableBinding(sprintf('Callable binding parameter "$%s" must have a single named class/interface type.', $parameter->getName()));
        }
        if ($type->isBuiltin()) {
            throw new InvalidCallableBinding(sprintf('Callable binding parameter "$%s" cannot use builtin type "%s".', $parameter->getName(), $type->getName()));
        }
        $dependency = $type->getName();
        if (!$this->container->has($dependency)) {
            throw new InvalidCallableBinding(sprintf('Callable binding parameter "$%s" requires container entry "%s", which is not available.', $parameter->getName(), $dependency));
        }
        return $this->container->get($dependency);
    }
}
