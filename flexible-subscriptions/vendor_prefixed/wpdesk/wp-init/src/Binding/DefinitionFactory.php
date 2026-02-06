<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\CallableDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\HookableDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\UnknownDefinition;
class DefinitionFactory
{
    public function create($value, ?string $hook, array $options = []): Definition
    {
        if (is_string($value) && class_exists($value) && is_subclass_of($value, Hookable::class, \true)) {
            return new HookableDefinition($value, $hook, $options);
        }
        if (is_callable($value)) {
            return new CallableDefinition($value, $hook, $options);
        }
        return new UnknownDefinition($value, $hook, $options);
    }
}
