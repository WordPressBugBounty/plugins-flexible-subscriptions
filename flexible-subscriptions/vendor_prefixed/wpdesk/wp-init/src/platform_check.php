<?php

namespace WPDesk\FlexibleSubscriptions\Vendor;

if (!(\PHP_VERSION_ID >= 70400)) {
    \add_action('admin_notices', function () {
        \printf('<div class="notice notice-error"><p>%s</p></div>', \esc_html__('The plugin cannot run on PHP versions older than 7.4. Please, contact your host and ask them to upgrade.', 'wp-init'));
    });
    return \false;
}
return \true;
