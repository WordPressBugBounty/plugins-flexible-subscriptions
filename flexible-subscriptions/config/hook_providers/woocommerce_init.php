<?php

use WPDesk\FlexibleSubscriptions\HookProvider\Account;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin;
use WPDesk\FlexibleSubscriptions\HookProvider\Ajax;
use WPDesk\FlexibleSubscriptions\HookProvider\Blocks;
use WPDesk\FlexibleSubscriptions\HookProvider\Cart;
use WPDesk\FlexibleSubscriptions\HookProvider\Checkout;
use WPDesk\FlexibleSubscriptions\HookProvider\Compatibility;
use WPDesk\FlexibleSubscriptions\HookProvider\Coupons;
use WPDesk\FlexibleSubscriptions\HookProvider\Email;
use WPDesk\FlexibleSubscriptions\HookProvider\Gateways;
use WPDesk\FlexibleSubscriptions\HookProvider\Marketing;
use WPDesk\FlexibleSubscriptions\HookProvider\OrderStatusListener;
use WPDesk\FlexibleSubscriptions\HookProvider\OverrideDataStores;
use WPDesk\FlexibleSubscriptions\HookProvider\Product;
use WPDesk\FlexibleSubscriptions\HookProvider\Subscription;
use WPDesk\FlexibleSubscriptions\HookProvider\SubscriptionOrderType;
use WPDesk\FlexibleSubscriptions\HookProvider\Telemetry;
use WPDesk\FlexibleSubscriptions\HookProvider\WcQueryFix;
use WPDesk\FlexibleSubscriptions\HookProvider\Api;

defined( 'ABSPATH' ) || exit;

return [
	Account\AccountEndpoints::class,
	Account\CustomerActionsController::class,
	Account\SubscriptionViewPage::class,
	Account\SubscriptionsPage::class,
	Admin\AccountEndpointsSettings::class,
	Admin\AccountSettings::class,
	Admin\CustomerLoginReminder::class,
	Admin\FailedPaymentRequestNotice::class,
	Admin\Menu::class,
	Admin\OrphanSubscriptionsRemover::class,
	Admin\QueryEnhancement::class,
	Admin\SettingsPage::class,
	Admin\SubscriptionEditedMessages::class,
	Admin\SubscriptionFilter::class,
	Admin\Subscription\AdminSubscriptionUpdate::class,
	Admin\Subscription\SubscriptionActions::class,
	Admin\Subscription\SubscriptionErrorsNotice::class,
	Admin\Subscription\SubscriptionsList::class,
	Ajax\AddingNonSubscriptionProducts::class,
	Ajax\LoadParentOrder::class,
	Cart\DisplayRecurringTotals::class,
	Cart\PaymentRequired::class,
	Cart\RecurrentCartAdder::class,
	Cart\ShippingDetails::class,
	Checkout\ForceCheckoutEnabled::class,
	Checkout\OrderRelatedSubscriptionsDetails::class,
	Checkout\SubscriptionCheckout::class,
	Checkout\RecurringShippingOptions::class,
	Compatibility\HookMapper::class,
	Compatibility\StatusHookMapper::class,
	Coupons\SubscriptionCouponTypes::class,
	Email\OrderAdditionalInfo::class,
	Gateways\AvailableGateways::class,
	Gateways\WooPaymentsIncompatibility::class,
	Marketing\EncourageRating::class,
	Marketing\SupportPage::class,
	OrderStatusListener::class,
	OverrideDataStores::class,
	Product\AddToCartTemplate::class,
	Product\ChangeAddButtonText::class,
	Product\SimpleSubscriptionProductSaver::class,
	Product\SubscriptionProductType::class,
	Product\VariableSubscriptionProductSaver::class,
	Product\VariationProductAid::class,
	SubscriptionOrderType::class,
	Subscription\PaymentRequestProcessor::class,
	Subscription\AutorecoverPastDueSubscriptions::class,
	Subscription\SubscriptionScheduledCancel::class,
	Subscription\SubscriptionScheduledExpire::class,
	Telemetry\TelemetryData::class,
	WcQueryFix::class,
	Api\Order::class,
];
