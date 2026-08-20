<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module\AbstractModule;
/**
 * Marks the plugin as using the WP Desk free plugin preset.
 */
final class FreePluginModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        foreach ([RequirementsModule::class, WPDeskTrackerModule::class] as $module_class) {
            if (!$context->has_module($module_class)) {
                throw new \LogicException(sprintf('FreePluginModule requires "%s" to be configured in modules.', $module_class));
            }
        }
    }
}
