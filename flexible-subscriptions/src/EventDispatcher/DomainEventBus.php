<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\EventDispatcher;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\EventDispatcher\EventDispatcherInterface;

class DomainEventBus implements EventDispatcherInterface {

	/** @var array<string, callable[]> */
	private array $listeners = [];

	public function subscribe( string $event_class, callable $listener ): void {
		$this->listeners[ $event_class ][] = $listener;
	}

	public function dispatch( object $event ): void {
		$event_class = get_class( $event );
		if ( ! isset( $this->listeners[ $event_class ] ) ) {
			return;
		}

		foreach ( $this->listeners[ $event_class ] as $listener ) {
			$listener( $event );
		}
	}
}
