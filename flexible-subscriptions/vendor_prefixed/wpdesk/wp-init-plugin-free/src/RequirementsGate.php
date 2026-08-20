<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootGate;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
/**
 * Stops plugin boot when WP Desk requirement checks fail.
 */
final class RequirementsGate implements BootGate
{
    private \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Requirement_Checker $checker;
    /**
     * @param array<string, mixed> $requirements
     */
    public function __construct(Plugin $plugin, array $requirements)
    {
        $this->checker = (new \WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Basic_Requirement_Checker_Factory())->create_from_requirement_array($plugin->get_basename(), $plugin->get_name(), $requirements, $plugin->get_slug());
    }
    public function can_boot(): bool
    {
        return $this->checker->are_requirements_met();
    }
    public function on_failure(): void
    {
        $this->checker->render_notices();
    }
}
