<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Checkout;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionCreator;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

/**
 * Create subscriptions based on cart items and newly created order.
 */
class SubscriptionCheckout implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private SubscriptionCreator $creator;

	private SubscriptionFinder $finder;

	private LoggerInterface $logger;

	public function __construct(
		SubscriptionCandidatesList $candidates,
		SubscriptionCreator $creator,
		SubscriptionFinder $finder,
		LoggerInterface $logger
	) {
		$this->candidates = $candidates;
		$this->creator    = $creator;
		$this->logger     = $logger;
		$this->finder     = $finder;
	}

	public function hooks(): void {
		add_action( 'woocommerce_checkout_order_processed', $this );
		add_action( 'woocommerce_store_api_checkout_order_processed', $this );
	}

	/** @param int $order_id */
	public function __invoke( $order_id ): void {
		if ( count( $this->candidates ) === 0 ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Make sure, any previous subscriptions (which are likely failed) are deleted before processing.
		$subscriptions = $this->finder->find_all_by_order( $order );
		foreach ( $subscriptions as $subscription ) {
			$subscription->delete( true );
		}

		$this->logger->debug(
			'Creating subscriptions from items in cart with order "{oid}"...',
			[
				'oid'               => $order->get_id(),
				'candidates'        => (string) $this->candidates,
				'order'             => $order,
			]
		);

		foreach ( $this->candidates as $group => $candidate ) {
			$this->logger->debug(
				'Building subscription from cart group "{gid}" with order "{oid}"',
				[
					'gid'                   => $group,
					'oid'                   => $order->get_id(),
					'candidate'             => (string) $candidate,
					'order'                 => $order,
				]
			);

			$subscription = $this->creator->build_subscription( $order, $candidate );

			do_action( 'fsub/subscription/new', $subscription, $order, $candidate );

			$this->logger->debug(
				'Created subscription "{sid}" from cart group "{gid}" with order "{oid}"',
				[
					'sid'                   => $subscription->get_id(),
					'gid'                   => $group,
					'oid'                   => $order->get_id(),
					'candidate'             => (string) $candidate,
					'order'                 => $order,
					'subscription'          => (string) $subscription,
				]
			);
		}

		$this->logger->debug( 'Finished creating subscriptions from cart items.' );
	}
}
