<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin;

/** @implements \ArrayAccess<array-key, mixed> */
final class Header implements \ArrayAccess
{
    /** @var array<string, mixed> */
    private array $header_data;
    /** @param array<string, mixed> $header_data */
    public function __construct(array $header_data)
    {
        $this->header_data = $header_data;
    }
    public function offsetExists($offset): bool
    {
        if (!is_string($offset)) {
            throw new \InvalidArgumentException('Header key must be a string');
        }
        return $this->has($offset);
    }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!is_string($offset)) {
            throw new \InvalidArgumentException('Header key must be a string');
        }
        return $this->get($offset);
    }
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Header cannot be modified');
    }
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Header cannot be modified');
    }
    /** @return mixed */
    public function get(string $key)
    {
        return $this->header_data[$key];
    }
    public function has(string $key): bool
    {
        return isset($this->header_data[$key]);
    }
}
