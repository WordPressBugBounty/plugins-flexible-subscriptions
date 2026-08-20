<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module\AbstractModule;
/**
 * Adds WP Desk basic requirements checks to the plugin boot sequence.
 */
final class RequirementsModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        if (!class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Basic_Requirement_Checker_Factory::class)) {
            throw new \LogicException('RequirementsModule requires "wpdesk/wp-basic-requirements" to be installed.');
        }
    }
    public function gates(ContainerInterface $container, BootstrapContext $context): array
    {
        $config = $context->module_config(self::class);
        $this->assert_known_keys($config, ['requirements']);
        $requirements = $config['requirements'] ?? null;
        if (!is_array($requirements) || $requirements === []) {
            throw new \LogicException('RequirementsModule requires non-empty "requirements" in module config.');
        }
        return [new RequirementsGate($context->plugin(), $requirements)];
    }
    /**
     * @param array<string, mixed> $config
     * @param string[]             $allowed_keys
     */
    private function assert_known_keys(array $config, array $allowed_keys): void
    {
        foreach (array_keys($config) as $key) {
            if (!is_string($key) || !in_array($key, $allowed_keys, \true)) {
                throw new \LogicException(sprintf('Unknown RequirementsModule config key "%s".', (string) $key));
            }
        }
    }
}
