<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Logger;

use WPDesk\FlexibleSubscriptions\Vendor\Monolog\Logger;
/*
 * @package WPDesk\Logger
 */
interface LoggerFactory
{
    /**
     * Returns created Logger
     *
     * @param string $name
     *
     * @return Logger
     */
    public function getLogger($name);
}
