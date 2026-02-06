<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding;

/**
 * Can be composed with other binders within {@see CompositeBinder} class.
 */
interface ComposableBinder extends Binder
{
    public function can_bind(Definition $def): bool;
}
