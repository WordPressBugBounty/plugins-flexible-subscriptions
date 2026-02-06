<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver\Legacy\HooksRegistry;
final class LegacyDriver implements HookDriver
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $container)
    {
        if (!class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class)) {
            throw new \LogicException('Legacy driver cannot be used as the plugin builder component is unavailable. Try running "composer require wpdesk/wp-builder".');
        }
        $this->container = $container;
    }
    public function register_hooks(): void
    {
        HooksRegistry::instance()->inject_container($this->container);
        $info = $this->container->get(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class);
        $class_name = $info->get_class_name();
        $p = new $class_name($info);
        add_action('plugins_loaded', [$p, 'init'], -50);
    }
}
