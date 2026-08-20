<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module;

/**
 * @implements \IteratorAggregate<class-string<Module>, Module>
 */
final class ModuleCollection implements \IteratorAggregate
{
    /** @var array<class-string<Module>, Module> */
    private array $modules = [];
    public function __construct(Module ...$modules)
    {
        foreach ($modules as $module) {
            $this->add($module);
        }
    }
    public function add(Module $module): void
    {
        $this->modules[get_class($module)] = $module;
    }
    public function has(string $module_class): bool
    {
        return array_key_exists($module_class, $this->modules);
    }
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->modules);
    }
}
