<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Formatting\Price;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;

class PaymentRequestTotal implements Format {

	private \WC_Order $payment_request;

	public function __construct( \WC_Order $payment_request ) {
		$this->payment_request = $payment_request;
	}

	public function __toString(): string {
		$item_count = $this->payment_request->get_item_count();
		return sprintf(
			// translators: %1$s Total price, %2$s Number of items.
			_n(
				'%1$s for %2$s item',
				'%1$s for %2$s items',
				(int) $item_count,
				'flexible-subscriptions'
			),
			$this->payment_request->get_formatted_order_total(),
			$item_count
		);
	}
}
