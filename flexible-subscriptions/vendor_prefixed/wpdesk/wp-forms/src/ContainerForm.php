<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Persistence\PersistentContainer;
/**
 * Persistent container support for forms.
 *
 * @package WPDesk\Forms
 */
interface ContainerForm
{
    /**
     * @param ContainerInterface $data
     *
     * @return void
     */
    public function set_data(ContainerInterface $data);
    /**
     * Put data from form into a container.
     *
     * @return void
     */
    public function put_data(PersistentContainer $container);
}
