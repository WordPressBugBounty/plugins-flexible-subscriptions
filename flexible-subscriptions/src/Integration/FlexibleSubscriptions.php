<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Integration;

use WPDesk\FlexibleSubscriptions\Subscription\Renewal\RequestPaymentStrategy;
use WPDesk\FlexibleSubscriptions\Subscription\Schedule;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinderInterface;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionUpdater;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;

/**
 * Used for plugin extensions. Request exposed services and data, by hooking into `fsub/initialized` with this object passed as parameter.
 *
 * @api
 */
final class FlexibleSubscriptions {

	private Plugin $plugin;

	private ContainerInterface $container;

	public function __construct( Plugin $plugin, ContainerInterface $container ) {
		$this->plugin    = $plugin;
		$this->container = $container;
	}

	public function version(): string {
		return $this->plugin->get_version();
	}

	public function renewal_strategy(): RequestPaymentStrategy {
		return $this->container->get( RequestPaymentStrategy::class );
	}

	public function subscription_finder(): SubscriptionFinder {
		return $this->container->get( SubscriptionFinderInterface::class );
	}

	public function subscription_updater(): SubscriptionUpdater {
		return $this->container->get( SubscriptionUpdater::class );
	}

	public function schedule(): Schedule {
		return $this->container->get( Schedule::class );
	}
}
