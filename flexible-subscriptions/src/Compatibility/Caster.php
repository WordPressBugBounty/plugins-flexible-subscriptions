<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Compatibility;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class Caster {

	/**
	 * @template T of object
	 * @param class-string<T> $class_name
	 * @param \WC_Data $source
	 *
	 * @return T
	 */
	public function cast_to( string $class_name, object $source ): object {
		if ( ! class_exists( $class_name ) ) {
			throw new \InvalidArgumentException( sprintf( 'Cannot cast to %s. Class does not exist.', $class_name ) );
		}

		if ( $source instanceof Subscription && is_subclass_of( $class_name, \WC_Order::class, true ) ) {
			return $this->cast_subscription( $class_name, $source );
		}

		throw new \InvalidArgumentException( sprintf( 'Cannot cast to %s.', $class_name ) );
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class_name
	 * @param \WC_Data $source
	 *
	 * @return T
	 */
	private function cast_subscription( string $class_name, object $source ): object {
		return new $class_name( $source->get_id() );
	}
}
