<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

final class SchedulePaymentRequestDecision {

	public const TRANSITION_EXPIRED = 'expired';
	public const TRANSITION_ON_HOLD = 'on-hold';

	public string $transition_to;

	public string $note;

	public function __construct( string $transition_to, string $note = '' ) {
		$this->transition_to = $transition_to;
		$this->note          = $note;
	}
}
