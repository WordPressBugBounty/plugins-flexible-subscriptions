<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker;

if (interface_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\Hookable::class) && interface_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\HookableCollection::class)) {
    class_alias(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\Hookable::class, Hookable::class);
    class_alias(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk\PluginBuilder\Plugin\HookableCollection::class, HookableCollection::class);
} else {
    require_once __DIR__ . '/Hookable.php';
    require_once __DIR__ . '/HookableCollection.php';
}
