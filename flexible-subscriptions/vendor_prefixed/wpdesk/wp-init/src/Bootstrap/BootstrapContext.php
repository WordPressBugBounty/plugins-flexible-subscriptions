<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\Configuration;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
final class BootstrapContext
{
    private Plugin $plugin;
    private Configuration $config;
    /** @var array<string, array<string, mixed>> */
    private array $modules;
    private string $environment;
    private bool $debug;
    /**
     * @param array<string, array<string, mixed>> $modules
     */
    public function __construct(Plugin $plugin, Configuration $config, array $modules, string $environment, bool $debug)
    {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->modules = $modules;
        $this->environment = $environment;
        $this->debug = $debug;
    }
    public function plugin(): Plugin
    {
        return $this->plugin;
    }
    public function config(): Configuration
    {
        return $this->config;
    }
    public function environment(): string
    {
        return $this->environment;
    }
    public function is_debug(): bool
    {
        return $this->debug;
    }
    public function is_development(): bool
    {
        return $this->environment === 'development';
    }
    /**
     * @return array<string, mixed>
     */
    public function module_config(string $module_class): array
    {
        return $this->modules[$module_class] ?? [];
    }
    public function has_module(string $module_class): bool
    {
        return array_key_exists($module_class, $this->modules);
    }
}
