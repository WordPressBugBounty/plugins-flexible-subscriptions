<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Definition\UnknownDefinition;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
/**
 * Add some PHP notices at dev environment, when a definition is unrecognized.
 */
final class DebugBindingLoader implements BindingDefinitions
{
    private BindingDefinitions $loader;
    public function __construct(BindingDefinitions $loader)
    {
        $this->loader = $loader;
    }
    public function load(): iterable
    {
        foreach ($this->loader->load() as $definition) {
            if ($definition instanceof UnknownDefinition) {
                @trigger_error(
                    // phpcs:ignore
                    sprintf('Trying to bind unknown value "%1$s". Currently wp-init can handle only simple callables and classes implementing "%2$s" interface', is_string($definition->value()) ? $definition->value() : json_encode($definition->value()), Hookable::class),
                    \E_USER_NOTICE
                );
            }
            yield $definition;
        }
    }
}
