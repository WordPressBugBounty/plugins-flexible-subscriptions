<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\DefinitionCollection;
final class ClusteredLoader implements BindingDefinitions
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
            if ($def->hook() === null) {
                yield $def;
                continue;
            }
            if (!isset($definitions[$def->hook()])) {
                $collection = new DefinitionCollection($def->hook());
                $definitions[$def->hook()] = $collection;
            }
            $definitions[$def->hook()]->add($def);
        }
        yield from $definitions;
    }
}
