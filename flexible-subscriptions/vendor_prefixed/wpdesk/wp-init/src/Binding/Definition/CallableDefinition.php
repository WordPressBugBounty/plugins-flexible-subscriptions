<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
/** @implements Definition<callable> */
class CallableDefinition implements Definition
{
    private ?string $hook;
    /** @var callable */
    private $callable;
    /** @var array<string, mixed> */
    private array $options;
    public function __construct(callable $callable, ?string $hook = null, array $options = [])
    {
        $this->callable = $callable;
        $this->hook = $hook;
        $this->options = $options;
    }
    public function hook(): ?string
    {
        return $this->hook;
    }
    public function value()
    {
        return $this->callable;
    }
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }
}
