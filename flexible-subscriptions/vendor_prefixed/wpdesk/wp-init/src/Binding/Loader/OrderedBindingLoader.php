<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
/**
 * @internal Binding loader implementation detail.
 */
final class OrderedBindingLoader implements BindingDefinitions
{
    private BindingDefinitions $loader;
    public function __construct(BindingDefinitions $loader)
    {
        $this->loader = $loader;
    }
    /** @return iterable<Definition<mixed>> */
    public function load(): iterable
    {
        $definitions = [];
        foreach ($this->loader->load() as $def) {
            $definitions[] = $def;
        }
        usort($definitions, static fn($a, $b): int => $a->option('priority') <=> $b->option('priority'));
        yield from array_reverse($definitions);
    }
}
