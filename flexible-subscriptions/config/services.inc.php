<?php

use WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtypeEnhancer;
use WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtype\FilterableSubtype;
use WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtype\RenewalSubtype;
use WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\SubscriptionRelatedOrdersEnhancer;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\OrderColumns;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\ProductView;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\QueryEnhancement;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\RelatedSubscription;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\SettingsPage;
use WPDesk\FlexibleSubscriptions\HookProvider\Admin\SubscriptionColumns;
use WPDesk\FlexibleSubscriptions\HookProvider\Cart\DisplayRecurringTotals;
use WPDesk\FlexibleSubscriptions\HookProvider\Checkout\OrderRelatedSubscriptionsDetails;
use WPDesk\FlexibleSubscriptions\HookProvider\Email\OrderAdditionalInfo;
use WPDesk\FlexibleSubscriptions\HookProvider\Marketing\SupportPage;
use WPDesk\FlexibleSubscriptions\HookProvider\Product\AddToCartTemplate;
use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\AutomaticRequest;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\CompositePaymentRequest;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\RenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock\WpOptionsRenewalLock;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\ManualRenewal;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RenewalFactory;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RequestPaymentStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\ZeroTotalRequest;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinderInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Clock\RequestTimeClock;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Resolver\DefaultFormFieldResolver;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\SimplePhpRenderer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\ChainResolver;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\DirResolver;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\WooTemplateResolver;
use function WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DI\autowire;
use function WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DI\get;

defined('ABSPATH') || exit;

return [
	ProductView::class                      => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	SubscriptionColumns::class              => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	SupportPage::class                      => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	SettingsPage::class                     => autowire()
		->constructorParameter( 'renderer', get( 'renderer.forms' ) ),

	RelatedSubscription::class              => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	OrderColumns::class                     => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	'WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription\*MetaBox' => autowire()
		->constructorParameter( 'renderer', get( 'renderer.admin' ) ),

	'WPDesk\FlexibleSubscriptions\HookProvider\Account\*Page' => autowire()
		->constructorParameter( 'renderer', get( 'renderer.front' ) ),

	'renderer.front'                        => static function ( Plugin $p ) {
		return new SimplePhpRenderer(
			new WooTemplateResolver( $p->get_path('/templates/') )
		);
	},
	'renderer.admin'                        => static function ( Plugin $p ) {
		return new SimplePhpRenderer(
			new ChainResolver(
				new DirResolver( $p->get_path('/templates/admin/') ),
				new DefaultFormFieldResolver()
			)
		);
	},

	'renderer.forms'                        => static function ( Plugin $p ) {
		return new SimplePhpRenderer(
			new ChainResolver(
				new DirResolver( $p->get_path('/templates/admin/forms/') ),
				new DefaultFormFieldResolver()
			)
		);
	},

	DisplayRecurringTotals::class           => autowire()
		->constructorParameter( 'renderer', get( 'renderer.front' ) ),

	OrderRelatedSubscriptionsDetails::class => autowire()
		->constructorParameter( 'renderer', get( 'renderer.front' ) ),

	AddToCartTemplate::class                => autowire()
		->constructorParameter( 'renderer', get( 'renderer.front' ) ),

	OrderAdditionalInfo::class              => autowire()
		->constructorParameter( 'renderer', get( 'renderer.front' ) ),

	PaymentMethodSeeker::class              => autowire(),

	/* Validator::class => autowire() */
	/* 	->constructorParameter( */
	/* 		'constraints', */
	/* 		[ */
	/* 			get( RegisteredUser::class ), */
	/* 			get( MatchingParentOrder::class ), */
	/* 		] */
	/* 	), */
	/**/
	RequestPaymentStrategy::class           => autowire( CompositePaymentRequest::class )
		->constructorParameter(
			'renewal_strategies',
			[
				get( ZeroTotalRequest::class ),
				get( ManualRenewal::class ),
				get( AutomaticRequest::class ),
			]
		),

	RenewalLock::class                      => autowire( WpOptionsRenewalLock::class ),

	ClockInterface::class                   => autowire( RequestTimeClock::class ),

	QueryEnhancement::class                 => autowire( QueryEnhancement::class )
	->constructorParameter(
		'enhancers',
		[
			get( SubscriptionRelatedOrdersEnhancer::class ),
			get( OrderSubtypeEnhancer::class ),
		]
	),

	OrderSubtypeEnhancer::class             => autowire( OrderSubtypeEnhancer::class )
		->constructorParameter(
			'enhancers',
			[
				get( RenewalSubtype::class ),
				get( FilterableSubtype::class ),
			]
		),

	SubscriptionFinderInterface::class      => autowire( SubscriptionFinder::class ),
	RenewalFactory::class => autowire(),
];
