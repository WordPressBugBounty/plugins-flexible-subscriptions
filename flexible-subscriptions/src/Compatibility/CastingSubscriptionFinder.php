<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Compatibility;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinderInterface;

class CastingSubscriptionFinder implements SubscriptionFinderInterface {

	/** @var SubscriptionFinder */
	private $finder;

	/** @var string */
	private $target_class;

	/** @var Caster */
	private $caster;

	public function __construct( SubscriptionFinder $finder, string $target_class ) {
		$this->finder       = $finder;
		$this->target_class = $target_class;
		$this->caster       = new Caster();
	}

	public function find( int $id ): ?Subscription {
		$subscription = $this->finder->find( $id );
		if ( $subscription instanceof Subscription ) {
			return $this->caster->cast_to( $this->target_class, $subscription );
		}

		return null;
	}

	public function subscription_exists(): bool {
		return $this->finder->subscription_exists();
	}

	/** @return iterable<Subscription> */
	public function find_by_customer( int $customer_id, int $offset = 0, ?int $limit = null ): iterable {
		$subscriptions = $this->finder->find_by_customer( $customer_id, $offset, $limit );
		return array_map(
			fn ( $subscription ) => $this->caster->cast_to( $this->target_class, $subscription ),
			$subscriptions
		);
	}

	/** @return iterable<Subscription> */
	public function find_all_by_order( \WC_Order $order ): iterable {
		$subscriptions = $this->finder->find_all_by_order( $order );
		return array_map(
			function ( $subscription ) {
				return $this->caster->cast_to( $this->target_class, $subscription );
			},
			$subscriptions
		);
	}

	public function find_by_payment_request( int $payment_request_id ): ?Subscription {
		$payment_request = $this->finder->find_by_payment_request( $payment_request_id );
		if ( $payment_request instanceof Subscription ) {
			return $this->caster->cast_to( $this->target_class, $payment_request );
		}

		return null;
	}

	public function count_by_customer( int $customer_id ): int {
		return $this->finder->count_by_customer( $customer_id );
	}

	public function find_all_by( array $criteria ): iterable {
		$subscriptions = $this->finder->find_all_by( $criteria );
		return array_map(
			fn ( $subscription ) => $this->caster->cast_to( $this->target_class, $subscription ),
			$subscriptions
		);
	}
}
