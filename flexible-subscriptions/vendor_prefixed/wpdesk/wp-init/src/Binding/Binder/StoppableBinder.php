<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\ComposableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\StoppableBinder as Stop;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder as BinderInstance;
class StoppableBinder implements ComposableBinder
{
    private ContainerInterface $container;
    /** @var Binder */
    private $binder;
    private bool $should_stop = \false;
    public function __construct(BinderInstance $b, ContainerInterface $c)
    {
        $this->binder = $b;
        $this->container = $c;
    }
    public function can_bind(Definition $def): bool
    {
        return $this->binder->can_bind($def);
    }
    public function bind(Definition $def): void
    {
        if ($this->should_stop === \true) {
            return;
        }
        $this->binder->bind($def);
        if ($this->can_be_stoppable($def)) {
            $binding = $this->container->get($def->value());
            if ($binding instanceof Stop && $binding->should_stop()) {
                $this->should_stop = \true;
            }
        }
    }
    private function can_be_stoppable(Definition $def): bool
    {
        return is_string($def->value()) && class_exists($def->value());
    }
}
