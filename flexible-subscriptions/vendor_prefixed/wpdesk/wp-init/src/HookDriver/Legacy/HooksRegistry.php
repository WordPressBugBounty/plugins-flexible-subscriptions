<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver\Legacy;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use Traversable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\Hookable;
/**
 * @internal Legacy migration support detail.
 *
 * @implements \IteratorAggregate<int,Hookable>
 */
final class HooksRegistry implements \IteratorAggregate
{
    private static ?HooksRegistry $instance = null;
    /** @var array<class-string<Hookable>|Hookable> */
    private array $callbacks = [];
    private ?ContainerInterface $container = null;
    private function __construct()
    {
    }
    public function inject_container(ContainerInterface $c): void
    {
        $this->container = $c;
    }
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function getIterator(): Traversable
    {
        return new \ArrayIterator(array_map(fn($hookable) => is_string($hookable) ? $this->container->get($hookable) : $hookable, $this->callbacks));
    }
    /** @param class-string<Hookable>|Hookable $hookable */
    public function add($hookable): void
    {
        $this->callbacks[] = $hookable;
    }
}
