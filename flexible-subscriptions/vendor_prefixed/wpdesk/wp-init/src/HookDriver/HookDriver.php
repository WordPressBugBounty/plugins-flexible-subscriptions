<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver;

/**
 * @internal Hook drivers are internal bootstrap implementation detail.
 *
 * Hook can be attached to WordPress in different ways, and this
 * interface decouples possible methods from our initialization system,
 * to make it more flexible.
 *
 * Even though hook registration is sort of the main purpose of this
 * library, it's better to encapsulate hook registration in a separate
 * class, so that it can be easily replaced with a different
 * implementation and composition.
 */
interface HookDriver
{
    public function register_hooks(): void;
}
