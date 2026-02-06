<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\ComposableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\HookableDefinition;
final class CompositeBinder implements Binder
{
    /** @var ComposableBinder[] */
    private array $binders;
    public function __construct(ComposableBinder ...$binders)
    {
        $this->binders = $binders;
    }
    public function add(ComposableBinder $binder): void
    {
        $this->binders[] = $binder;
    }
    public function bind(Definition $def): void
    {
        if (is_iterable($def)) {
            foreach ($def as $d) {
                $this->bind($d);
            }
            return;
        }
        foreach ($this->binders as $binder) {
            if ($binder->can_bind($def)) {
                $binder->bind($def);
                break;
            }
        }
    }
}
