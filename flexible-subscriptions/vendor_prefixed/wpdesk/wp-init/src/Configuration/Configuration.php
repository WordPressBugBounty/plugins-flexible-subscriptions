<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration;

class Configuration implements ReadableConfig
{
    /** @var array<string, mixed> */
    private array $config;
    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
    /** @param mixed $value */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }
    public function remove(string $key): void
    {
        unset($this->config[$key]);
    }
}
