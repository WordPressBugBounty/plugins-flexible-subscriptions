<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver;

final class CompositeDriver implements HookDriver
{
    /** @var HookDriver[] */
    private array $drivers;
    public function __construct(HookDriver ...$drivers)
    {
        $this->drivers = $drivers;
    }
    public function register_hooks(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->register_hooks();
        }
    }
    public function add(HookDriver $driver): void
    {
        $this->drivers[] = $driver;
    }
}
