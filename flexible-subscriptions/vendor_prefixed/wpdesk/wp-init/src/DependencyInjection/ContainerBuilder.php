<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection;

use WPDesk\FlexibleSubscriptions\Vendor\DI\Container;
use WPDesk\FlexibleSubscriptions\Vendor\DI\ContainerBuilder as DiBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\DI\Definition\Source\DefinitionSource;
final class ContainerBuilder
{
    private DiBuilder $original_builder;
    public function __construct(DiBuilder $original_builder)
    {
        $this->original_builder = $original_builder;
    }
    /**
     * Add definitions to the container.
     *
     * @param string|array|DefinitionSource ...$definitions
     *  Can be an array of definitions, the name of a file containing definitions or
     *  a DefinitionSource object.
     *
     * @return $this
     */
    public function add_definitions(...$definitions): self
    {
        $this->original_builder->addDefinitions(...$definitions);
        return $this;
    }
    public function build(): Container
    {
        return $this->original_builder->build();
    }
}
