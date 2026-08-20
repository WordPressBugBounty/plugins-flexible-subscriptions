<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\DefinitionFactory;
/**
 * @internal Binding loader implementation detail.
 */
class ArrayDefinitions implements BindingDefinitions
{
    /** @var array<int|string, mixed> */
    private array $bindings;
    private DefinitionFactory $factory;
    /** @param array<int|string, mixed> $bindings */
    public function __construct(array $bindings, ?DefinitionFactory $factory = null)
    {
        $this->bindings = $bindings;
        $this->factory = $factory ?? new DefinitionFactory();
    }
    public function load(): iterable
    {
        yield from $this->normalize($this->bindings);
    }
    /**
     * @param iterable<int|string,mixed> $bindings
     *
     * @return iterable<Definition<mixed>>
     */
    private function normalize(iterable $bindings): iterable
    {
        foreach ($bindings as $key => $value) {
            if (is_callable($value)) {
                yield $this->create($value, $key);
            } elseif (is_array($value)) {
                if (isset($value['handler'])) {
                    // Single item with handler.
                    yield $this->create($value['handler'], $key, $value);
                } else {
                    // Multiple items.
                    foreach ($value as $unit) {
                        if (is_array($unit) && isset($unit['handler'])) {
                            yield $this->create($unit['handler'], $key, $unit);
                        } else {
                            yield $this->create($unit, $key);
                        }
                    }
                }
            } else {
                yield $this->create($value, $key);
            }
        }
    }
    /**
     * @param mixed $value
     * @param int|string $hook
     * @param array<string, mixed> $options
     *
     * @return Definition<mixed>
     */
    private function create($value, $hook, array $options = []): Definition
    {
        return $this->factory->create($value, is_int($hook) ? null : $hook, $options);
    }
}
