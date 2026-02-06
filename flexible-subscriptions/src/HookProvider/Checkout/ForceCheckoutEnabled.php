<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Checkout;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * If current WooCommerce settings prevent creating account during checkout, require customer to log in.
 */
final class ForceCheckoutEnabled implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	public function __construct( SubscriptionCandidatesList $candidates ) {
		$this->candidates = $candidates;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_checkout_registration_enabled', [ $this, 'enable_registration_during_checkout' ] );
		add_filter( 'woocommerce_checkout_registration_required', [ $this, 'require_registration_during_checkout' ] );
	}

	public function require_registration_during_checkout( bool $account_required ): bool {
		if ( count( $this->candidates ) > 0 && ! is_user_logged_in() ) {
			return true;
		}

		return $account_required;
	}

	public function enable_registration_during_checkout( bool $registration_enabled ): bool {
		if ( count( $this->candidates ) > 0 && ! is_user_logged_in() ) {
			return true;
		}

		return $registration_enabled;
	}
}
