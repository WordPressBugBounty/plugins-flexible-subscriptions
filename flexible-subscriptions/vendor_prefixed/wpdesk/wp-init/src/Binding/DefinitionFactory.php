<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\CallableDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\HookableDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Exception\InvalidBindingDefinition;
class DefinitionFactory
{
    /**
     * @param mixed $value
     * @param array<string, mixed> $options
     *
     * @return Definition<mixed>
     */
    public function create($value, ?string $hook, array $options = []): Definition
    {
        if (is_string($value) && class_exists($value) && is_subclass_of($value, Hookable::class)) {
            return new HookableDefinition($value, $hook, $options);
        }
        if (is_callable($value)) {
            return new CallableDefinition($value, $hook, $options);
        }
        throw new InvalidBindingDefinition(sprintf('Invalid binding for hook "%s". Expected a hookable class-string or callable, got %s.', $hook ?? '<bootstrap>', $this->describe_value($value)));
    }
    /**
     * @param mixed $value
     */
    private function describe_value($value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }
        if (is_string($value)) {
            if (class_exists($value)) {
                return sprintf('class-string "%s", but it does not implement %s', $value, Hookable::class);
            }
            return sprintf('string "%s", which is not an autoloadable hookable class or callable', $value);
        }
        return gettype($value);
    }
}
