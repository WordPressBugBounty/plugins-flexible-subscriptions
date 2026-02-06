<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

/**
 * @implements \IteratorAggregate<class-string<Extension>, Extension>
 */
class ExtensionsSet implements \IteratorAggregate
{
    /** @var array<class-string<Extension>, Extension> */
    private array $extensions = [];
    public function __construct(Extension ...$extensions)
    {
        foreach ($extensions as $extension) {
            $this->add($extension);
        }
    }
    public function add(Extension $extension): void
    {
        $class = \get_class($extension);
        $this->extensions[$class] = $extension;
    }
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->extensions);
    }
}
