<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer;

final class SubscriptionRelatedOrdersEnhancer implements QueryEnhancer {

	public function enhance_query( $query ): array {
		$subscription_related_orders = isset( $_GET['_subscription_related_orders'] ) ? absint( wp_unslash( $_GET['_subscription_related_orders'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $subscription_related_orders ) ) {
			return $query;
		}

		$query['post_parent'] = $subscription_related_orders;

		return $query;
	}
}
