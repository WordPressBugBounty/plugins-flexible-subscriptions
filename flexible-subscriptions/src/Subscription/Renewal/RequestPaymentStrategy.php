<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;

/**
 * There may be multiple strategies for processing a payment request for
 * a subscription. Any valid processor should indicate if it supports
 * current subscription and prepared payment request. Then, main flow is
 * executed in `request_payment` method, which may consis of any
 * additional actions required to process the payment request.
 */
interface RequestPaymentStrategy {

	public function supports( Subscription $subscription, \WC_Order $payment_request ): bool;

	public function request_payment( \WC_Order $payment_request ): void;
}
