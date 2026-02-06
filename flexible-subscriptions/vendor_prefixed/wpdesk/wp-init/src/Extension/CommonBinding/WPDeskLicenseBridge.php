<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\License\LicenseServer\PluginRegistrator;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\License\LicenseServer\PluginVersionInfo;
class WPDeskLicenseBridge implements Hookable
{
    private Plugin $plugin;
    private string $product_id;
    /** @var string[] */
    private array $shops;
    private LoggerInterface $logger;
    public function __construct(Plugin $plugin, string $product_id, array $shops, LoggerInterface $logger)
    {
        $this->plugin = $plugin;
        $this->product_id = $product_id;
        $this->shops = $shops;
        $this->logger = $logger;
    }
    public function hooks(): void
    {
        add_action('plugins_loaded', $this, -50);
    }
    public function __invoke(): void
    {
        // Backward compatibility with wp-builder hook.
        if (apply_filters('wpdesk_can_register_plugin', \true, $this->plugin) === \false) {
            return;
        }
        $plugin_info = new PluginVersionInfo($this->plugin->get_name(), $this->plugin->get_version(), $this->product_id, $this->plugin->get_slug(), $this->plugin->get_basename(), $this->shops);
        $registrator = new PluginRegistrator($plugin_info);
        $registrator->initialize_license_manager();
    }
}
