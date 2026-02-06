<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

class ZeroTotalRequest implements RequestPaymentStrategy {

	public function request_payment( \WC_Order $payment_request ): void {
		$payment_request->payment_complete();
	}

	public function supports( Subscription $subscription, \WC_Order $payment_request ): bool {
		return $payment_request->get_total( 'edit' ) === 0;
	}
}
