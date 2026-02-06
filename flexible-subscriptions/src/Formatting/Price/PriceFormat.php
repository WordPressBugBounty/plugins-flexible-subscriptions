<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Formatting\Price;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidate;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Format\Format;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class PriceFormat implements Format {

	public const TYPE_SUBTOTAL = 0;
	public const TYPE_SHIPPING = 1;
	public const TYPE_TAX      = 2;

	/** @var string|float */
	private $value;

	private WPInterval $frequency;

	/** @param string|float $value */
	public function __construct( $value, WPInterval $frequency ) {
		$this->value     = $value;
		$this->frequency = $frequency;
	}

	public static function subscription( Subscription $subscription ): self {
		return new self(
			$subscription->get_total(),
			$subscription->get_billing_frequency()
		);
	}

	public static function candidate( SubscriptionCandidate $candidate, int $type = self::TYPE_SUBTOTAL ): self {
		$value = 0.0;
		switch ( $type ) {
			case self::TYPE_SUBTOTAL:
				$value = $candidate->get_displayed_subtotal();
				break;
			case self::TYPE_SHIPPING:
				$value = $candidate->get_total();
				break;
			case self::TYPE_TAX:
				$value = $candidate->get_total_tax();
				break;
		}

		return new self(
			$value,
			$candidate->get_billing_frequency()
		);
	}

	public function __toString(): string {
		return sprintf(
			// translators: %1$s Total price, %2$s Billing frequency.
			__( '%1$s / %2$s', 'flexible-subscriptions' ),
			wc_price( (float) $this->value ),
			$this->frequency->to_readable_string()
		);
	}
}
