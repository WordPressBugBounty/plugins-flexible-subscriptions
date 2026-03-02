<?php

namespace WPDesk\FlexibleSubscriptions\Subscription;

final class BillingPeriodCalculator {

	public function calculate_next_period( \DateTimeInterface $start_date, \DateInterval $interval ): \DatePeriod {
		return new \DatePeriod( $start_date, $interval, $start_date->add( $interval ) );
	}

	public function calculate_activation_period( Subscription $subscription, \DateTimeInterface $now ): \DatePeriod {
		if (
			$subscription->has_trial() &&
			$subscription->is_during_first_cycle() &&
			$subscription->get_current_period_start() instanceof \DateTimeInterface &&
			$subscription->get_current_period_end() instanceof \DateTimeInterface
		) {
			return new \DatePeriod(
				$subscription->get_current_period_start(),
				$subscription->get_billing_frequency(),
				$subscription->get_current_period_end()
			);
		}

		return $this->calculate_next_period( $now, $subscription->get_billing_frequency() );
	}
}
