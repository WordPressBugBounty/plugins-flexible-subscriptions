<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension;

use WPDesk\FlexibleSubscriptions\Vendor\DI\Definition\Helper\AutowireDefinitionHelper;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\ArrayDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader\BindingDefinitions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\Configuration;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\ReadableConfig;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding\RequirementsCheck;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding\WPDeskLicenseBridge;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding\WPDeskTrackerBridge;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Logger\SimpleLoggerFactory;
class ConditionalExtension implements Extension
{
    public function bindings(ContainerInterface $c): BindingDefinitions
    {
        $config = $c->get(Configuration::class);
        $bindings = [];
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Basic_Requirement_Checker::class)) {
            $bindings[] = ['priority' => -10, 'handler' => RequirementsCheck::class];
        }
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\License\LicenseServer\PluginRegistrator::class)) {
            $bindings[] = WPDeskLicenseBridge::class;
        }
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Tracker::class)) {
            $bindings['plugins_loaded'] = WPDeskTrackerBridge::class;
        }
        return new ArrayDefinitions($bindings);
    }
    public function build(ContainerBuilder $builder, Plugin $plugin, ReadableConfig $config): void
    {
        $definitions = [];
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Basic_Requirement_Checker::class)) {
            $definitions[RequirementsCheck::class] = new AutowireDefinitionHelper();
        }
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\License\LicenseServer\PluginRegistrator::class)) {
            $definitions[WPDeskLicenseBridge::class] = (new AutowireDefinitionHelper())->constructorParameter('product_id', $config->get('product_id'))->constructorParameter('shops', (array) $config->get('shops', []));
        }
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Tracker::class)) {
            $definitions[WPDeskTrackerBridge::class] = new AutowireDefinitionHelper();
        }
        if (class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Logger\SimpleLoggerFactory::class)) {
            $definitions[LoggerInterface::class] = static function (ContainerInterface $c) {
                $p = $c->get(Plugin::class);
                return (new SimpleLoggerFactory($p->get_slug(), ['level' => $c->has('logger.level') ? $c->get('logger.level') : 'debug', 'action_level' => $c->has('logger.action_level') ? $c->get('logger.action_level') : null]))->getLogger();
            };
        }
        $builder->add_definitions($definitions);
    }
}
