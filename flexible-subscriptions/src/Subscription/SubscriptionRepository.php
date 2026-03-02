<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\EventDispatcher\EventDispatcherInterface;

final class SubscriptionRepository extends SubscriptionFinder {

	private EventDispatcherInterface $event_dispatcher;

	private \wpdb $wpdb;

	public function __construct( EventDispatcherInterface $event_dispatcher, \wpdb $wpdb ) {
		$this->event_dispatcher = $event_dispatcher;
		$this->wpdb             = $wpdb;
	}

	public function save( Subscription $subscription ): void {
		try {
			$this->wpdb->query( 'START TRANSACTION' );
			$subscription->save();
			$this->wpdb->query( 'COMMIT' );
		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		foreach ( $subscription->release_events() as $event ) {
			$this->event_dispatcher->dispatch( $event );
		}
	}
}
