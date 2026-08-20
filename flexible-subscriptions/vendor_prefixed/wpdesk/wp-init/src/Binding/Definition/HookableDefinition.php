<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
/** @implements Definition<class-string<Hookable>> */
class HookableDefinition implements Definition
{
    private ?string $hook;
    /** @var class-string<Hookable> */
    private string $hookable;
    /** @var array<string, mixed> */
    private array $options;
    /**
     * @param class-string<Hookable> $hookable
     * @param array<string, mixed> $options
     */
    public function __construct(string $hookable, ?string $hook = null, array $options = [])
    {
        $this->hook = $hook;
        $this->hookable = $hookable;
        $this->options = $options;
    }
    public function hook(): ?string
    {
        return $this->hook;
    }
    public function value()
    {
        return $this->hookable;
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }
}
