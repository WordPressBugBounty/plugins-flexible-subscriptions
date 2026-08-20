<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding;

interface Binder
{
    /** @param Definition<mixed> $def */
    public function bind(Definition $def): void;
}
