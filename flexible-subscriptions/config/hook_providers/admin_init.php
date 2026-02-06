<?php

use WPDesk\FlexibleSubscriptions\HookProvider\Admin;

defined( 'ABSPATH' ) || exit;

return [
	Admin\AdminScripts::class,
	Admin\OrderColumns::class,
	Admin\ProductView::class,
	Admin\RelatedSubscription::class,
	Admin\RenewalMetaBackfill::class,
	Admin\SubscriptionColumns::class,
	Admin\Subscription\PaymentRequestsMetaBox::class,
	Admin\Subscription\ScheduleMetaBox::class,
	Admin\Subscription\SubscriptionDataMetaBox::class,
	Admin\SupportedGateways::class,
];
