<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class PaymentMethodSeeker {

	private ?\WC_Payment_Gateways $gateways = null;

	/**
	 * Check if provided gateway is actually available for subscription.
	 */
	public function available_gateway( string $gateway_id ): ?\WC_Payment_Gateway {
		$this->initialize();

		foreach ( $this->gateways->get_available_payment_gateways() as $gateway ) {
			if ( $gateway_id === $gateway->id ) {
				return $gateway;
			}
		}

		return null;
	}

	/** @return array<string, string> */
	public function get_valid_payment_methods( Subscription $subscription ): array {
		$this->initialize();

		$valid_gateways = [ 'manual' => __( 'Manual Renewal', 'flexible-subscriptions' ) ];

		foreach ( $this->gateways->get_available_payment_gateways() as $gateway_id => $gateway ) {
			if ( $gateway->supports( 'subscription_payment_method_change_admin' ) ) {
				$valid_gateways[ $gateway_id ] = $gateway->get_title();
			} elseif ( ! $subscription->is_manual() && $gateway_id === $subscription->get_payment_method() ) {
				$valid_gateways[ $gateway_id ] = $gateway->get_title();
			}
		}

		return $valid_gateways;
	}

	public function get_payment_gateway( string $gateway_id ): ?\WC_Payment_Gateway {
		$this->initialize();

		foreach ( $this->gateways->payment_gateways() as $gateway ) {
			if ( $gateway_id === $gateway->id ) {
				return $gateway;
			}
		}

		return null;
	}

	private function initialize(): void {
		$this->gateways = WC()->payment_gateways();
	}
}
