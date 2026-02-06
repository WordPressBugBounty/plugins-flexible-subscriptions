<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class ManualRenewal implements RequestPaymentStrategy {

	public function request_payment( WC_Order $payment_request ): void {
		$payment_request->add_order_note( __( 'Manual renewal order awaiting customer payment.', 'flexible-subscriptions' ) );
	}

	public function supports( Subscription $subscription, WC_Order $payment_request ): bool {
		return apply_filters( 'fsub/payment/manual_renewal/enabled', true ) && $subscription->is_manual();
	}
}
