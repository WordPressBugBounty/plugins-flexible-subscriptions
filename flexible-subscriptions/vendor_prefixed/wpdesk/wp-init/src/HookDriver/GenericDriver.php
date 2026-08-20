<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Binder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
/**
 * @internal Hook driver implementation detail.
 */
class GenericDriver implements HookDriver
{
    private BindingDefinitions $definitions;
    private Binder $binder;
    public function __construct(BindingDefinitions $definitions, Binder $binder)
    {
        $this->definitions = $definitions;
        $this->binder = $binder;
    }
    public function register_hooks(): void
    {
        // Load has to be deffered until plugins_loaded because classes may implement or extend interfaces/classes which doesn't exist yet.
        add_action('plugins_loaded', function (): void {
            foreach ($this->definitions->load() as $definition) {
                if ($definition->hook() === null) {
                    $this->binder->bind($definition);
                    continue;
                }
                add_action($definition->hook(), fn() => $this->binder->bind($definition));
            }
        }, -50);
    }
}
