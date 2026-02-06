<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

class SubscriptionFinder implements SubscriptionFinderInterface {

	public function find( int $id ): ?Subscription {
		$subscription = wc_get_order( $id );
		if ( $subscription instanceof Subscription ) {
			return $subscription;
		}

		return null;
	}

	public function find_all_by( array $criteria ): iterable {
		$subscriptions = wc_get_orders(
			array_merge(
				[
					'type'   => 'fsb_subscription',
					'status' => 'any',
					'limit'  => -1,
				],
				$criteria
			)
		);
		return $subscriptions;
	}

	public function subscription_exists(): bool {
		$results = wc_get_orders(
			[
				'type'   => 'fsb_subscription',
				'status' => 'all',
				'limit'  => 1,
				'return' => 'ids',
			]
		);
		return count( $results ) === 1;
	}

	/** @return iterable<Subscription>&\Countable */
	public function find_by_customer( int $customer_id, int $offset = 0, ?int $limit = null ): iterable {
		$subscriptions = wc_get_orders(
			[
				'type'        => 'fsb_subscription',
				'limit'       => $limit ?? get_option( 'posts_per_page', 10 ),
				'offset'      => $offset,
				'customer_id' => $customer_id,
				'status'      => 'any',
			]
		);
		return $subscriptions;
	}

	/** @return iterable<Subscription>&\Countable */
	public function find_all_by_order( \WC_Order $order ): iterable {
		$subscriptions = wc_get_orders(
			[
				'type'        => 'fsb_subscription',
				'limit'       => -1,
				'parent'      => $order->get_id(),
				'status'      => 'any',
			]
		);
		return $subscriptions;
	}

	public function find_by_payment_request( int $payment_request_id ): ?Subscription {
		$payment_request = wc_get_order( $payment_request_id );

		if ( ! $payment_request instanceof \WC_Order ) {
			return null;
		}

		return $this->find( $payment_request->get_parent_id( 'edit' ) );
	}

	public function count_by_customer( int $customer_id ): int {
		$result = wc_get_orders(
			[
				'type'        => 'fsb_subscription',
				'limit'       => 1,
				'customer_id' => $customer_id,
				'paginate'    => true,
				'return'      => 'ids',
				'status'      => 'any',
			]
		);
		return $result->total;
	}
}
