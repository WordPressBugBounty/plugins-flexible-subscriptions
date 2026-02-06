<?php

namespace WPDesk\FlexibleSubscriptions\HookProvider\Telemetry;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class TelemetryData implements HookProvider {
	public function hooks(): void {
		add_filter( 'wpdesk_tracker_data', $this );
	}

	public function __invoke( array $data ): array {
		$subscriptions = wc_get_orders(
			[
				'type'        => 'fsb_subscription',
				'limit'       => -1,
				'paginate'    => false,
				'return'      => 'objects',
				'status'      => 'any',
			]
		);

		$products = wc_get_products(
			[
				'limit'  => -1,
				'status' => 'any',
				'type'   => [ 'fsb-subscription', 'fsb-variable-subscription' ],
			]
		);

		$q       = new \WP_Query(
			[
				'post_type'              => 'shop_coupon',
				'posts_per_page'         => -1,
				'nopaging'               => true,
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'cache_results'          => false,
				'fields'                 => 'ids',
			]
		);
		$coupons = array_filter(
			array_map(
				fn ( $i ) => new \WC_Coupon( $i ),
				$q->get_posts()
			),
			fn ( $c ) => in_array( $c->get_discount_type(), [ 'sign_up_fee', 'sign_up_fee_percent', 'recurring_fee', 'recurring_percent' ], true )
		);

		$data['flexible_subscriptions'] = [
			'subscriptions_count' => $this->group_by(
				$subscriptions,
				fn ( $sub ) => $sub->get_status()
			),
			'products'            => [
				'general_count'                       => count( $products ),
				'virtual_count'                       => $this->count_by(
					$products,
					fn ( $p ) => $p->is_virtual()
				),
				'product_type'                        => $this->group_by(
					$products,
					fn ( $p ) => $p->get_type()
				),
				'sign_up_fee_usage_count'             => $this->count_by(
					$products,
					fn ( $p ) => $p->get_meta( '_fsb_subscription_sign_up_fee' )
				),
				'subscription_expiration_usage_count' => $this->count_by(
					$products,
					fn ( $p ) => $p->get_meta( '_fsb_total_billing_cycles' )
				),
				'trial_usage_count'                   => $this->count_by(
					$products,
					fn ( $p ) => $p->get_meta( '_fsb_subscription_trial_length' )
				),
				'used_trial_units'                    => $this->group_by(
					$products,
					fn ( $p ) => $p->get_meta( '_fsb_subscription_trial_period' )
				),
				'used_subscription_unit'              => $this->group_by(
					$products,
					fn ( $p ) => $p->get_meta( '_fsb_subscription_period' )
				),
			],
			'coupons_usage' => $this->group_by(
				$coupons,
				fn ( $c ) => $c->get_discount_type()
			),
		];

		return $data;
	}

	private function group_by( array $data, callable $fn ): array {
		return array_reduce(
			$data,
			function ( $acc, $i ) use ( $fn ) {
				$unit = $fn( $i );
				if ( ! isset( $acc[ $unit ] ) ) {
					$acc[ $unit ] = 0;
				}

				$acc[ $unit ]++;

				return $acc;
			},
			[]
		);
	}

	private function count_by( array $data, callable $fn ): int {
		return count(
			array_filter(
				$data,
				fn ( $i ) => ! empty( $fn( $i ) )
			)
		);
	}
}
