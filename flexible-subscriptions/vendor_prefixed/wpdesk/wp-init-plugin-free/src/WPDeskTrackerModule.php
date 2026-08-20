<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\ArrayDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module\AbstractModule;
use function WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DI\autowire;
/**
 * Adds WP Desk tracker bootstrap hooks.
 */
final class WPDeskTrackerModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        if (!class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\Tracker::class)) {
            throw new \LogicException('WPDeskTrackerModule requires "wpdesk/wp-wpdesk-tracker" to be installed.');
        }
        $shops = $this->validate_shops($context->module_config(self::class));
        if (!$context->has_module(WPDeskLoggerModule::class)) {
            (new WPDeskLoggerModule())->build($builder, $context);
        }
        $builder->add_definitions([WPDeskTrackerBridge::class => autowire()->constructorParameter('shops', $shops)]);
    }
    public function bindings(ContainerInterface $container, BootstrapContext $context): BindingDefinitions
    {
        return new ArrayDefinitions(['plugins_loaded' => WPDeskTrackerBridge::class]);
    }
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, string>
     */
    private function validate_shops(array $config): array
    {
        $this->assert_known_keys($config, ['shops']);
        if (!isset($config['shops']) || !is_array($config['shops'])) {
            throw new \LogicException('WPDeskTrackerModule requires "shops" in module config.');
        }
        if (!isset($config['shops']['default'])) {
            throw new \LogicException('WPDeskTrackerModule requires "shops.default" in module config.');
        }
        $result = [];
        foreach ($config['shops'] as $locale => $url) {
            if (!is_string($locale) || $locale === '') {
                throw new \LogicException('WPDeskTrackerModule shop keys must be non-empty strings.');
            }
            if (!is_string($url) || $url === '' || filter_var($url, \FILTER_VALIDATE_URL) === \false) {
                throw new \LogicException(sprintf('WPDeskTrackerModule shop "%s" must be a valid URL.', $locale));
            }
            $result[$locale] = $url;
        }
        return $result;
    }
    /**
     * @param array<string, mixed> $config
     * @param string[]             $allowed_keys
     */
    private function assert_known_keys(array $config, array $allowed_keys): void
    {
        foreach (array_keys($config) as $key) {
            if (!is_string($key) || !in_array($key, $allowed_keys, \true)) {
                throw new \LogicException(sprintf('Unknown WPDeskTrackerModule config key "%s".', (string) $key));
            }
        }
    }
}
