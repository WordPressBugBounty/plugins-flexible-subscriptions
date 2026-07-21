<?php
/**
 * Plugin Name: Flexible Subscriptions
 * Plugin URI: https://www.wpdesk.net/sk/flexible-subscriptions-plugin/
 * Description: Flexible Subscriptions is a WooCommerce extension that allows you to create flexible subscription products.
 * Version: 1.7.19
 * Author: WP Desk
 * Author URI: https://www.wpdesk.net/sk/flexible-subscriptions-author/
 * Text Domain: flexible-subscriptions
 * Domain Path: /lang/
 * Requires at least: 6.4
 * Tested up to: 7.0
 * WC requires at least: 10.5
 * WC tested up to: 10.9
 * License: GPL v2 or later
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * Copyright 2024 WP Desk Ltd.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

defined( 'ABSPATH' ) || exit;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Init;

require __DIR__ . '/vendor/autoload.php';

Init::setup(
	[
		'hook_resources_path' => 'config/hook_providers',
		'services'            => 'config/services.inc.php',

		'requirements'        => [
			'php'          => '7.4',
			'wp'           => '6.3',
			'repo_plugins' => [
				[
					'name'      => 'woocommerce/woocommerce.php',
					'nice_name' => 'WooCommerce',
					'version'   => '8.9',
				],
			],
		],
	]
)->boot();
