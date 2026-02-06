<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
class I18n implements Hookable
{
    private Plugin $plugin;
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }
    public function hooks(): void
    {
        if (did_action('plugins_loaded')) {
            $this->__invoke();
        } else {
            add_action('plugins_loaded', $this);
        }
    }
    public function __invoke(): void
    {
        $relative_path = str_replace(\WP_PLUGIN_DIR . '/', '', $this->plugin->get_path($this->plugin->header()->get('DomainPath')));
        \load_plugin_textdomain($this->plugin->get_slug(), \false, $relative_path);
    }
}
