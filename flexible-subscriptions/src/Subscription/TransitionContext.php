<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

/**
 * Context for lifecycle operations.
 *
 * Keep this intentionally tiny; it allows distinguishing manual/admin transitions from automatic ones.
 */
final class TransitionContext {

	public bool $manual;

	public string $note;

	public string $reason;

	public function __construct( bool $manual = false, string $note = '', string $reason = '' ) {
		$this->manual = $manual;
		$this->note   = $note;
		$this->reason = $reason;
	}

	public static function manual( string $reason = '', string $note = '' ): self {
		return new self( true, $note, $reason );
	}

	public static function system( string $reason = '', string $note = '' ): self {
		return new self( false, $note, $reason );
	}
}
