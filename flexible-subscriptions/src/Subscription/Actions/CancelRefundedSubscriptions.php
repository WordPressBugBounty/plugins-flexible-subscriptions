<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Actions;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionRepository;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;

final class CancelRefundedSubscriptions {

	private SubscriptionRepository $repository;

	private ClockInterface $clock;

	public function __construct( SubscriptionRepository $repository, ClockInterface $clock ) {
		$this->repository = $repository;
		$this->clock      = $clock;
	}

	public function process( WC_Order $order ): void {
		/** @var Subscription[] $subscriptions */
		$subscriptions = [];

		if ( Renewal::is_renewal_order( $order ) ) {
			$subscription = $this->repository->find_by_payment_request( $order->get_parent_id() );
			if ( $subscription instanceof Subscription ) {
				$subscriptions[] = $subscription;
			}
		} else {
			$subscriptions = $this->repository->find_all_by_order( $order );
		}

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_pending_cancel() ) {
				$subscription->cancel( $this->clock->now(), TransitionContext::system( 'order_refunded' ) );
				$subscription->add_order_note(
					wp_kses( sprintf( __( 'Subscription cancelled for refunded order %1$s#%2$s%3$s.', 'flexible-subscriptions' ), sprintf( '<a href="%s">', esc_url( $order->get_edit_order_url() ) ), $order->get_order_number(), '</a>' ), [ 'a' => [ 'href' => true ] ] )
				);
				$this->repository->save( $subscription );
			}
		}
	}
}
