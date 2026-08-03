<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Settings\PaymentOptions;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class ManualRenewal implements RequestPaymentStrategy {

	private PaymentOptions $payment_options;

	public function __construct( PaymentOptions $payment_options ) {
		$this->payment_options = $payment_options;
	}

	public function request_payment( WC_Order $payment_request ): void {
		$payment_request->add_order_note( __( 'Manual renewal order awaiting customer payment.', 'flexible-subscriptions' ) );
	}

	public function supports( Subscription $subscription, WC_Order $payment_request ): bool {
		return apply_filters(
			'fsub/payment/manual_renewal/enabled',
			$this->payment_options->manual_renewal_enabled()
		) && $subscription->is_manual();
	}
}
