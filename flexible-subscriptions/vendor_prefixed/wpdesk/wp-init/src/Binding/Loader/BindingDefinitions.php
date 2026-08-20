<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
interface BindingDefinitions
{
    /**
     * @return iterable<Definition<mixed>>
     */
    public function load(): iterable;
}
