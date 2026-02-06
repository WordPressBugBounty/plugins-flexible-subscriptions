<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Constraints;

/**
 * @implements \IteratorAggregate<ConstraintViolation>
 */
final class ConstraintViolationList implements \IteratorAggregate, \Countable {

	/** @var ConstraintViolation[] */
	private array $violations = [];

	public function add( ConstraintViolation $violation ): void {
		$this->violations[] = $violation;
	}

	public function getIterator(): \Traversable {
		return new \ArrayIterator( $this->violations );
	}

	public function count(): int {
		return count( $this->violations );
	}
}
