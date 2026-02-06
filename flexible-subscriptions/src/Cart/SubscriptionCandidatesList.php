<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Cart;

/**
 * Own implementation of WC Cart focused on recurrent products.
 *
 * Products in cart may have different subscriptions, thus our main cart
 * have to be able to iterate over all its items, and to know how to
 * create a subscription for each line item. This cart is actually a bag
 * for subsidiary items, which are also represented as carts (are they?).
 *
 * @implements \IteratorAggregate<string,SubscriptionCandidate>
 */
final class SubscriptionCandidatesList implements \IteratorAggregate, \Countable {

	/** @var array<string, SubscriptionCandidate> */
	private array $candidates = [];

	public function add_candidate( SubscriptionCandidate $candidate ): void {
		$this->candidates[ $candidate->get_group() ] = $candidate;
	}

	public function getIterator(): \Traversable {
		return new \ArrayIterator( $this->candidates );
	}

	public function count(): int {
		return count( $this->candidates );
	}

	public function __toString(): string {
		return json_encode(
			array_map(
				static fn( SubscriptionCandidate $candidate ) => json_decode( (string) $candidate ),
				$this->candidates
			)
		);
	}
}
