<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\StoppableBinder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\Configuration;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
class RequirementsCheck implements StoppableBinder
{
    private \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Requirement_Checker $checker;
    public function __construct(Plugin $plugin, Configuration $config)
    {
        $this->checker = (new \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Basic_Requirement_Checker_Factory())->create_from_requirement_array($plugin->get_basename(), $plugin->get_name(), $config->has('requirements') ? $config->get('requirements') : [], $plugin->get_slug());
    }
    public function hooks(): void
    {
        if ($this->should_stop()) {
            $this->checker->render_notices();
        }
    }
    public function should_stop(): bool
    {
        return !$this->checker->are_requirements_met();
    }
}
