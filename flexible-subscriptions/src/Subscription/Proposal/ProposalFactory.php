<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\RecurringLineItem;
use WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model\SubscriptionProposal;

final class ProposalFactory {

	private RecurringCouponFilter $coupon_filter;

	public function __construct( RecurringCouponFilter $coupon_filter ) {
		$this->coupon_filter = $coupon_filter;
	}

	/**
	 * @param \WC_Cart $cart
	 *
	 * @return SubscriptionProposal[]
	 */
	public function from_wc_cart( \WC_Cart $cart ): array {
		$proposals = [];

		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			// Todo: explicit check should be hidden into domain
			if ( ! $product instanceof SubscriptionProduct ) {
				continue;
			}
			$wrapper = new SubscriptionProductWrapper( $product );

			$plan = $wrapper->get_plan();

			$key = $plan->get_hash();

			if ( ! isset( $proposals[ $key ] ) ) {
				$proposals[ $key ] = new SubscriptionProposal( $key, $plan );
			}

			$proposals[ $key ]->add_item( RecurringLineItem::from_cart_item( $cart_item ) );
		}

		$applied_coupons = $cart->get_applied_coupons();

		foreach ( $proposals as $proposal ) {
			foreach ( $applied_coupons as $coupon_code ) {
				if ( $this->coupon_filter->is_compatible( $coupon_code ) ) {
					$proposal->add_coupon( $coupon_code );
				}
			}
		}

		// todo: here is wc_cart context
		return $proposals;
	}

	public function from_order( \WC_Order $order ): array {
		$proposals = [];

		foreach ( $order->get_items( 'line_item' ) as $order_item ) {
			if ( ! $order_item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $order_item->get_product();
			if ( ! $product instanceof SubscriptionProduct ) {
				continue;
			}

			$wrapper = new SubscriptionProductWrapper( $product );
			$plan    = $wrapper->get_plan();
			$key     = $plan->get_hash();

			if ( ! isset( $proposals[ $key ] ) ) {
				$proposals[ $key ] = new SubscriptionProposal( $key, $plan );
			}

			$proposals[ $key ]->add_item( RecurringLineItem::from_order_item( $order_item ) );
		}

		$applied_coupons = $order->get_coupon_codes();

		foreach ( $proposals as $proposal ) {
			foreach ( $applied_coupons as $coupon_code ) {
				if ( $this->coupon_filter->is_compatible( $coupon_code, $proposal ) ) {
					$proposal->add_coupon( $coupon_code );
				}
			}
		}

		return $proposals;
	}
}
