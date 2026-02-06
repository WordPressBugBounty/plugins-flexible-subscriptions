<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Api;

use WPDesk\FlexibleSubscriptions\Cart\ApiSubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Cart\SimilarOrderItems;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionCreator;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class Order implements HookProvider {

	private SubscriptionCreator $creator;

	private SubscriptionFinder $finder;

	private LoggerInterface $logger;

	public function __construct(
		SubscriptionCreator $creator,
		SubscriptionFinder $finder,
		LoggerInterface $logger
	) {
		$this->creator = $creator;
		$this->finder  = $finder;
		$this->logger  = $logger;
	}

	public function hooks(): void {
		add_action(
			'woocommerce_rest_insert_shop_order_object',
			[ $this, 'create_subscription_from_api_order' ],
			10,
			3
		);
		add_filter(
			'woocommerce_rest_prepare_shop_order_object',
			[ $this, 'attach_subscriptions_to_response' ],
			10,
			3
		);
	}

	public function create_subscription_from_api_order( $order, $request, $creating ) {
		if ( ! $creating ) {
			return;
		}

		$subscriptions = $this->finder->find_all_by_order( $order );
		foreach ( $subscriptions as $subscription ) {
			$subscription->delete( true );
		}

		$grouped_items = new SimilarOrderItems();

		foreach ( $order->get_items() as $item ) {
			$grouped_items->add( $item );
		}

		if ( ! $grouped_items->has_items() ) {
			return;
		}

		foreach ( $grouped_items as $group_hash => $items ) {
			try {
				$candidate = new ApiSubscriptionCandidate( $order, $items );

				$subscription = $this->creator->build_subscription( $order, $candidate );

				do_action( 'fsub/subscription/new', $subscription, $order, $candidate );

				$item_ids = array_map( fn( $i ) => $i->get_id(), $items );

				$this->logger->debug(
					'API: Created subscription "{sid}" for items "{iids}"',
					[
						'sid'  => $subscription->get_id(),
						'iids' => implode( ', ', $item_ids ),
					]
				);
			} catch ( \Exception $e ) {
				$this->logger->error( 'API error: ' . $e->getMessage() );
			}
		}
	}

	public function attach_subscriptions_to_response( $response, $order, $request ) {
		$subscriptions     = $this->finder->find_all_by_order( $order );
		$subscriptions_ids = array_map(
			static function ( $subscription ) {
				return $subscription->get_id();
			},
			$subscriptions
		);

		$data                  = $response->get_data();
		$data['subscriptions'] = $subscriptions_ids;
		$response->set_data( $data );

		return $response;
	}
}
