<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\HookDriver\Legacy;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\Conditional;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\HookablePluginDependant;
/**
 * @internal Legacy migration support detail.
 */
trait HookableParent
{
    /** @var HooksRegistry|null */
    private $registry;
    /**
     * @param class-string<Hookable>|Hookable $hookable_object
     */
    public function add_hookable($hookable_object)
    {
        if ($this->registry === null) {
            $this->registry = HooksRegistry::instance();
        }
        $this->registry->add($hookable_object);
    }
    /**
     * @param class-string<Hookable> $class_name
     *
     * @return false|Hookable
     */
    public function get_hookable_instance_by_class_name($class_name)
    {
        if ($this->registry === null) {
            return \false;
        }
        foreach ($this->registry as $hookable_object) {
            if ($hookable_object instanceof $class_name) {
                return $hookable_object;
            }
        }
        return \false;
    }
    /**
     * Run hooks method on all hookable objects.
     */
    protected function hooks_on_hookable_objects()
    {
        if ($this->registry === null) {
            return;
        }
        foreach ($this->registry as $hookable_object) {
            if ($hookable_object instanceof Conditional && !$hookable_object::is_needed()) {
                continue;
            }
            if ($hookable_object instanceof HookablePluginDependant) {
                $hookable_object->set_plugin($this);
            }
            $hookable_object->hooks();
        }
    }
}
