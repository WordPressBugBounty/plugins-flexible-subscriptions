<?php

namespace WPDesk\FlexibleSubscriptions\Vendor;

use WPDesk\FlexibleSubscriptions\Vendor\DI\Definition\Helper\AutowireDefinitionHelper;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding\CustomOrdersTableCompatibility;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\CommonBinding\I18n;
return [\wpdb::class => static function () {
    global $wpdb;
    return $wpdb;
}, I18n::class => new AutowireDefinitionHelper(), CustomOrdersTableCompatibility::class => new AutowireDefinitionHelper()];
