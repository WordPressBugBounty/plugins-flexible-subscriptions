<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\EventListener;

use WPDesk\FlexibleSubscriptions\Subscription\Events\BillingFrequencyUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\CancelledDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\EndDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\NextPaymentDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\StatusUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionActivated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionCancelled;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionExpired;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionPaused;
use WPDesk\FlexibleSubscriptions\Subscription\Schedule;

final class SyncScheduleOnSubscriptionChange {

	private Schedule $schedule;

	public function __construct( Schedule $schedule ) {
		$this->schedule = $schedule;
	}

	public function on_cancelled_date_updated( CancelledDateUpdated $event ): void {
		if ( $event->new_date instanceof \DateTimeImmutable ) {
			$this->schedule->remove_payment_request( $event->subscription );
		}
	}

	public function on_end_date_updated( EndDateUpdated $event ): void {
		if ( $event->date ) {
			$this->schedule->cancel( $event->subscription );
		}
	}

	public function on_next_payment_date_updated( NextPaymentDateUpdated $event ): void {
		if ( $event->new_date ) {
			$this->schedule->schedule_payment_request( $event->subscription );
		}
	}

	public function on_status_updated( StatusUpdated $event ): void {
		if ( $event->new_status === 'on-hold' ) {
			$this->schedule->remove_payment_request( $event->subscription );
		}
	}

	public function on_billing_frequency_updated( BillingFrequencyUpdated $event ): void {
		if ( $event->subscription->is_active() ) {
			$this->schedule->schedule_payment_request( $event->subscription );
		}
	}

	public function on_subscription_activated( SubscriptionActivated $event ): void {
		$this->schedule->schedule_payment_request( $event->subscription );
	}

	public function on_subscription_paused( SubscriptionPaused $event ): void {
		$this->schedule->remove_payment_request( $event->subscription );
	}

	public function on_subscription_cancelled( SubscriptionCancelled $event ): void {
		$this->schedule->remove_payment_request( $event->subscription );
		$this->schedule->cancel( $event->subscription );
	}

	public function on_subscription_expired( SubscriptionExpired $event ): void {
		$this->schedule->remove_payment_request( $event->subscription );
	}
}
