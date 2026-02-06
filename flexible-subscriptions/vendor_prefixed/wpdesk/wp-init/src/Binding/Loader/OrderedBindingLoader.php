<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

final class OrderedBindingLoader implements BindingDefinitions
{
    private BindingDefinitions $loader;
    public function __construct(BindingDefinitions $loader)
    {
        $this->loader = $loader;
    }
    public function load(): iterable
    {
        $definitions = [];
        foreach ($this->loader->load() as $def) {
            $definitions[] = $def;
        }
        usort($definitions, fn($a, $b): int => $a->option('priority') <=> $b->option('priority'));
        yield from array_reverse($definitions, \false);
    }
}
