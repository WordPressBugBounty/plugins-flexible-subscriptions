<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Actions\ActivateSubscription;
use WPDesk\FlexibleSubscriptions\Subscription\Actions\ProcessPaidRenewal;
use WPDesk\FlexibleSubscriptions\Subscription\Actions\CancelRefundedSubscriptions;
use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class OrderStatusListener implements HookProvider {

	private CancelRefundedSubscriptions $refunded_order_processor;

	private ActivateSubscription $activate_subscription;

	private ProcessPaidRenewal $process_paid_renewal;

	public function __construct(
		ActivateSubscription $activate_subscription,
		ProcessPaidRenewal $process_paid_renewal,
		CancelRefundedSubscriptions $refunded_order_processor
	) {
		$this->refunded_order_processor = $refunded_order_processor;
		$this->activate_subscription    = $activate_subscription;
		$this->process_paid_renewal     = $process_paid_renewal;
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

		if ( Renewal::is_renewal_order( $order ) ) {
			$this->process_paid_renewal->execute( Renewal::from_order( $order ), $old_status );
		} else {
			$this->activate_subscription->execute( $order );
		}
	}

	/** @param int $order_id */
	public function on_order_refund( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->refunded_order_processor->process( $order );
	}
}
