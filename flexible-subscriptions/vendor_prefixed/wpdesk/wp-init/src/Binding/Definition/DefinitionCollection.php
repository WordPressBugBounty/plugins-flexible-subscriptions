<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;

use IteratorAggregate;
use Traversable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
/**
 * @implements Definition<mixed>
 * @implements IteratorAggregate<mixed,Definition<mixed>>
 */
class DefinitionCollection implements Definition, \IteratorAggregate
{
    private ?string $hook;
    /** @var Definition[] */
    private array $defs;
    /** @var array<string, mixed> */
    private array $options;
    public function __construct(?string $hook = null, array $options = [])
    {
        $this->hook = $hook;
        $this->options = $options;
    }
    public function hook(): ?string
    {
        return $this->hook;
    }
    public function value()
    {
        yield from $this->defs;
    }
    public function add(Definition $def): void
    {
        $this->defs[] = $def;
    }
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }
    public function getIterator(): Traversable
    {
        yield from $this->defs;
    }
}
