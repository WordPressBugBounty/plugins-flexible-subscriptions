<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap;

interface BootGate
{
    public function can_boot(): bool;
    public function on_failure(): void;
}
