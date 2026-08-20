<?php

use WPDesk\FlexibleSubscriptions\HookProvider\Admin\PluginLinks;
use WPDesk\FlexibleSubscriptions\HookProvider\Blocks\BlocksIntegrationLoader;
use WPDesk\FlexibleSubscriptions\HookProvider\Blocks\ExtendStoreApi;
use WPDesk\FlexibleSubscriptions\HookProvider\Gateways;
use WPDesk\FlexibleSubscriptions\HookProvider\Subscription;
use WPDesk\FlexibleSubscriptions\HookProvider\Init;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\AjaxHandler;

defined( 'ABSPATH' ) || exit;

return [
	[
		'handler'  => Gateways\GatewayCompatibilityLoader::class,
		'priority' => -10,
	],
		BlocksIntegrationLoader::class,
		ExtendStoreApi::class,
	'plugins_loaded'        => [
		Init::class,
		PluginLinks::class,
		function () {
			( new AjaxHandler() )->hooks();
		},
	],
	'action_scheduler_init' => [
		Subscription\AutorecoverOnHoldSubscriptions::class,
	],
];
