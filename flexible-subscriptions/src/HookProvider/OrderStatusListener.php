<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\TransitionContext;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class OrderStatusListener implements HookProvider {

	private SubscriptionFinder $finder;

	private SubscriptionLifecycleManager $lifecycle;

	private LoggerInterface $logger;

	public function __construct( SubscriptionFinder $finder, SubscriptionLifecycleManager $lifecycle, LoggerInterface $logger ) {
		$this->finder    = $finder;
		$this->lifecycle = $lifecycle;
		$this->logger    = $logger;
	}

	public function hooks(): void {
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
		add_action( 'woocommerce_order_fully_refunded', [ $this, 'on_order_refund' ] );
	}

	/**
	 * @param string $order_id
	 * @param string $old_status
	 * @param string $new_status
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function on_status_changed( $order_id, $old_status, $new_status, $order ): void {
		if ( $order instanceof Subscription ) {
			return;
		}
		if ( $order->get_parent_id() > 0 ) {
			$this->payment_request_status_changed( $order, $old_status, $new_status );
		} else {
			$this->subscription_parent_status_changed( $order, $old_status, $new_status );
		}
	}

	/** @param int $order_id */
	public function on_order_refund( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/** @var Subscription[] */
		$subscriptions = [];

		if ( $order->get_parent_id() > 0 ) {
			// Payment request.
			$subscription = $this->finder->find_by_payment_request( $order->get_parent_id() );
			if ( $subscription instanceof Subscription ) {
				$subscriptions[] = $subscription;
			}
		} else {
			// Parent order.
			$subscriptions = $this->finder->find_all_by_order( $order );
		}

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_pending_cancel() ) {
				$this->lifecycle->transition( $subscription, 'cancelled', TransitionContext::system( 'order_refunded' ) );
				$subscription->add_order_note(
					wp_kses( sprintf( __( 'Subscription cancelled for refunded order %1$s#%2$s%3$s.', 'flexible-subscriptions' ), sprintf( '<a href="%s">', esc_url( $order->get_edit_order_url() ) ), $order->get_order_number(), '</a>' ), [ 'a' => [ 'href' => true ] ] )
				);
			}
		}
	}

	private function payment_request_status_changed( WC_Order $order, string $old_status, string $new_status ): void {
		$payment_completed = $this->is_payment_completed( $old_status, $new_status, $order );

		if ( $payment_completed === false ) {
			return;
		}

		$subscription = $this->finder->find_by_payment_request( $order->get_id() );
		if ( ! $subscription instanceof Subscription ) {
			return;
		}

		$was_active_before_payment = $subscription->has_status( 'active' );

		if ( 'failed' === $old_status ) {
			do_action( 'fsub/subscription/payment_request/updated_failing_payment_method', $subscription, $order );
		}

		$this->logger->debug(
			'Changed status of order "{oid}" (payment request) linked to subscription "{sid}".',
			[
				'oid'          => $order->get_id(),
				'sid'          => $subscription->get_id(),
				'order'        => $order,
				'subscription' => (string) $subscription,
				'order_status' => [
					'previous' => $old_status,
					'current'  => $new_status,
				],
			]
		);
		if ( ! $subscription->has_status( 'active' ) ) {
			$this->lifecycle->transition( $subscription, 'active', TransitionContext::system( 'payment_request_paid' ) );
			$this->logger->debug(
				'Subscription "{sid}" sucesfully activated after submitted payment request "{oid}".',
				[

					'oid'          => $order->get_id(),
					'sid'          => $subscription->get_id(),
					'order'        => $order,
					'subscription' => (string) $subscription,
					'order_status' => [
						'previous' => $old_status,
						'current'  => $new_status,
					],
				]
			);
		} else {
			$this->logger->debug(
				'Subscription "{sid}" does not require any action because it is already active.',
				[
					'sid'               => $subscription->get_id(),
					'order'             => $order,
					'subscription'      => (string) $subscription,
					'order_status'      => [
						'previous' => $old_status,
						'current'  => $new_status,
					],
				]
			);
		}

		$this->lifecycle->process_paid_renewal( $subscription, $order, $was_active_before_payment );
	}

	private function subscription_parent_status_changed( WC_Order $order, string $old_status, string $new_status ): void {
		$payment_completed = $this->is_payment_completed( $old_status, $new_status, $order );

		if ( $payment_completed === false ) {
			return;
		}

		$subscriptions = $this->finder->find_all_by_order( $order );

		if ( count( $subscriptions ) === 0 ) {
			return;
		}

		$this->logger->debug(
			'Initial order "{oid}", which holds subscription changed its status.',
			[
				'oid'          => $order->get_id(),
				'order'        => $order,
				'order_status' => [
					'previous' => $old_status,
					'current'  => $new_status,
				],
			]
		);

		foreach ( $subscriptions as $subscription ) {
			$this->logger->debug( 'Processing subscription "{sid}"...', [ 'sid' => $subscription->get_id() ] );
			if ( ! $subscription->has_status( 'active' ) ) {
				$this->lifecycle->transition( $subscription, 'active', TransitionContext::system( 'initial_order_paid' ) );
				$this->logger->debug(
					'Subscription "{sid}" sucessfully activated.',
					[
						'sid'                       => $subscription->get_id(),
						'subscription'              => (string) $subscription,
						'order'                     => $order,
					]
				);
			} else {
				$this->logger->debug(
					'Subscription "{sid}" does not require any action because it is already active.',
					[
						'sid'                       => $subscription->get_id(),
						'subscription'              => (string) $subscription,
						'order'                     => $order,
					]
				);
			}
		}

		$this->logger->debug(
			'Finished updating subscriptions related to initial order "{oid}".',
			[
				'oid'          => $order->get_id(),
				'order'        => $order,
				'order_status' => [
					'previous' => $old_status,
					'current'  => $new_status,
				],
			]
		);
	}

	private function is_payment_completed( string $old_status, string $new_status, \WC_Order $order ): bool {
		return in_array( $new_status, wc_get_is_paid_statuses(), true )
			&& in_array( $old_status, apply_filters( 'woocommerce_valid_order_statuses_for_payment', [ 'pending', 'on-hold', 'failed' ], $order ), true );
	}
}
