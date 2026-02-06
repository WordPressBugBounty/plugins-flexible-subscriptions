<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class ShippingDetails implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	public function __construct( SubscriptionCandidatesList $candidates ) {
		$this->candidates = $candidates;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_shipping_package_name', [ $this, 'change_initial_shipping_package_name' ], 1 );
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function change_initial_shipping_package_name( $name ) {
		if ( count( $this->candidates ) > 0 ) {
			return __( 'Initial Shipment', 'flexible-subscriptions' );
		}

		return $name;
	}
}
