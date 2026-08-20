<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\Tracker;
/**
 * Registers WP Desk tracker UI and hooks.
 */
final class WPDeskTrackerBridge implements Hookable
{
    private Plugin $plugin;
    /** @var array<string, string> */
    private array $shops;
    private LoggerInterface $logger;
    /**
     * @param array<string, string> $shops
     */
    public function __construct(Plugin $plugin, array $shops, LoggerInterface $logger)
    {
        $this->plugin = $plugin;
        $this->shops = $shops;
        $this->logger = $logger;
    }
    public function hooks(): void
    {
        $shop_url = $this->shops[get_user_locale()] ?? $this->shops['default'];
        Tracker::register_plugin($this->plugin->get_file(), $this->plugin->get_slug(), $shop_url, $this->plugin->get_name(), $this->logger);
    }
}
