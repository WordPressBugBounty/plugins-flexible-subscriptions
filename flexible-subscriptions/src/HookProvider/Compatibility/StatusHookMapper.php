<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Compatibility;

use WC_Subscription;
use WPDesk\FlexibleSubscriptions\Compatibility\Caster;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

/**
 * Maps Flexible Subscriptions status events to WooCommerce Subscriptions hooks.
 */
final class StatusHookMapper implements HookProvider {

	private Caster $caster;

	public function __construct( Caster $caster ) {
		$this->caster = $caster;
	}

	public function hooks(): void {
		add_action( 'fsub/subscription/status/updated', [ $this, 'map_status_hooks' ], 10, 3 );
	}

	/**
	 * @param Subscription $subscription
	 * @param string $new_status
	 * @param string|null $previous_status
	 */
	public function map_status_hooks( Subscription $subscription, string $new_status = '', ?string $previous_status = null ): void {
		if ( '' !== $new_status ) {
			do_action( "woocommerce_subscription_status_{$new_status}", $this->caster->cast_to( WC_Subscription::class, $subscription ) );
		}

		if ( $previous_status === null || $previous_status === '' || $previous_status === $new_status ) {
			return;
		}

		do_action( "woocommerce_subscription_status_{$previous_status}_to_{$new_status}", $this->caster->cast_to( WC_Subscription::class, $subscription ) );
		do_action( 'woocommerce_subscription_status_updated', $this->caster->cast_to( WC_Subscription::class, $subscription ), $new_status, $previous_status );
		do_action( 'woocommerce_subscription_status_changed', $subscription->get_id(), $previous_status, $new_status, $this->caster->cast_to( WC_Subscription::class, $subscription ) );
	}
}
