<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding;

/**
 * @template T
 */
interface Definition
{
    public function hook(): ?string;
    /** @return T */
    public function value();
    /** @return mixed */
    public function option(string $name);
}
