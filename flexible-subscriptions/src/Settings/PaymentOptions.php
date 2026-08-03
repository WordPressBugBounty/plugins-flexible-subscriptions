<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Settings;

class PaymentOptions {

	public const REQUIRE_PAYMENT_ON_ZERO_TOTAL = 'fsb_require_payment_on_trial';
	public const MANUAL_RENEWAL_ENABLED        = 'fsb_manual_renewal_enabled';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function require_payment_on_zero_total(): bool {
		return $this->settings->get_bool( self::REQUIRE_PAYMENT_ON_ZERO_TOTAL, false );
	}

	public function manual_renewal_enabled(): bool {
		return $this->settings->get_bool( self::MANUAL_RENEWAL_ENABLED, false );
	}
}
