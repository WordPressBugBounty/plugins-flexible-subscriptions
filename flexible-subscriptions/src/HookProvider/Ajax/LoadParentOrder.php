<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Ajax;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class LoadParentOrder implements HookProvider {

	public function hooks(): void {
		add_action( 'wp_ajax_fsb_get_customer_orders', $this );
	}

	public function __invoke(): void {
		check_ajax_referer( 'get-customer-orders', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$customer_orders = [];
		$user_id         = absint( $_POST['user_id'] ?? null );
		$orders          = wc_get_orders(
			[
				'customer'       => $user_id,
				'post_type'      => 'shop_order',
				'posts_per_page' => '-1',
			]
		);

		foreach ( $orders as $order ) {
			$customer_orders[ $order->get_id() ] = $order->get_order_number();
		}

		wp_send_json( $customer_orders );
	}
}
