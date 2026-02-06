<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
/** @implements Definition<mixed> */
class UnknownDefinition implements Definition
{
    private ?string $hook;
    /** @var mixed */
    private $value;
    /** @var array<string, mixed> */
    private array $options;
    public function __construct($value, ?string $hook = null, array $options = [])
    {
        $this->value = $value;
        $this->hook = $hook;
        $this->options = $options;
    }
    public function hook(): ?string
    {
        return $this->hook;
    }
    public function value()
    {
        return $this->value;
    }
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }
}
