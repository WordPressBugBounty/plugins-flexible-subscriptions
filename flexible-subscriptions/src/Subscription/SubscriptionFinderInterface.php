<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

interface SubscriptionFinderInterface {

	public function find( int $id ): ?Subscription;

	public function subscription_exists(): bool;

	/** @return iterable<Subscription> */
	public function find_by_customer( int $customer_id, int $offset = 0, ?int $limit = null ): iterable;

	/** @return iterable<Subscription> */
	public function find_all_by_order( \WC_Order $order ): iterable;

	public function find_by_payment_request( int $payment_request_id ): ?Subscription;

	public function count_by_customer( int $customer_id ): int;

	public function find_all_by( array $criteria ): iterable;
}
