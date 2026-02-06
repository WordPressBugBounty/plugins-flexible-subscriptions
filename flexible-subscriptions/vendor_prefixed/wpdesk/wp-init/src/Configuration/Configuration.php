<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration;

/**
 * @implements \ArrayAccess<string, mixed>
 */
class Configuration implements ReadableConfig, \ArrayAccess
{
    /** @var array<string, mixed> */
    private array $config;
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
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }
    public function remove(string $key): void
    {
        unset($this->config[$key]);
    }
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new \InvalidArgumentException('Cannot set value without key.');
        }
        $this->set($offset, $value);
    }
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
