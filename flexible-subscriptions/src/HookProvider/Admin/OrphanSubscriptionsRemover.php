<?php

declare( strict_types=1 );

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WP_User;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Removes subscriptions on user removal.
 */
class OrphanSubscriptionsRemover implements HookProvider {

	private SubscriptionFinder $finder;

	private LoggerInterface $logger;

	public function __construct( SubscriptionFinder $finder, LoggerInterface $logger ) {
		$this->finder = $finder;
		$this->logger = $logger;
	}

	public function hooks(): void {
		add_action( 'delete_user', $this );
		add_action( 'delete_user_form', [ $this, 'display_existing_subscriptions_notice' ], 10, 2 );
	}

	public function __invoke( int $user_id ): void {
		$subscriptions = $this->finder->find_by_customer( $user_id );
		$deleted_user  = get_userdata( $user_id );
		$current_user  = is_user_logged_in() ? wp_get_current_user() : null;

		foreach ( $subscriptions as $subscription ) {
			if ( $current_user ) {
				$subscription_order = $subscription->get_parent();
				if ( $subscription_order ) {
					$subscription_order->add_order_note(
						sprintf(
						/* translators: 1: subscription ID, 2: user display name */
							__(
								'The subscription #%1$d was deleted after the customer was removed by %2$s.',
								'flexible-subscriptions'
							),
							$subscription->get_id(),
							$current_user->display_name
						),
						0,
						true
					);
				}
			}

			$this->logger->debug(
				'Subscription (ID: {subscription_id}) of user "{customer_email}" with status "{subscription_status}" has been deleted.',
				[
					'subscription_id'     => $subscription->get_id(),
					'customer_email'      => $deleted_user->get( 'user_email' ),
					'subscription_status' => $subscription->get_status(),
				]
			);

			$subscription->delete();
		}
	}

	public function display_existing_subscriptions_notice( WP_User $user, array $user_ids ): void {
		$users_with_subscriptions = 0;
		$output_message           = '';

		foreach ( $user_ids as $user_id ) {
			$subscriptions = $this->finder->find_by_customer( $user_id );

			if ( empty( $subscriptions ) ) {
				continue;
			}

			++$users_with_subscriptions;
			$total_subs  = count( $subscriptions );
			$active_subs = $this->count_active_subscriptions( $subscriptions );

			$output_message .= $this->build_user_subs_message( $user_id, $total_subs, $active_subs );
		}

		if ( $users_with_subscriptions > 0 ) {
			/* translators: %s: either "user" or "users". */
			$warning = sprintf(
				__( 'Warning: The %s you are about to delete has purchased subscriptions. Deleting them will cancel any active subscriptions immediately.', 'flexible-subscriptions' ),
				_n( 'user', 'users', $users_with_subscriptions, 'flexible-subscriptions' )
			);

			echo '<p style="color: var(--wc-red)"><strong>' . esc_html( $warning ) . '</strong></p>';
			echo wp_kses_post( $output_message );
		}
	}

	/**
	 * @param Subscription[] $subscriptions The subscriptions to evaluate.
	 */
	private function count_active_subscriptions( iterable $subscriptions ): int {
		$active_count = 0;
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_active() ) {
				++$active_count;
			}
		}

		return $active_count;
	}

	private function build_user_subs_message( int $user_id, int $total_subs, int $active_subs ): string {
		$deleted_user = get_userdata( $user_id );

		$text = sprintf(
		/* translators: 1: User ID, 2: User Display Name, 3: Number of subscriptions, 4: "subscription" or "subscriptions", 5: Number of active subscriptions, 6: "active subscription" or "active subscriptions" */
			__(
				'User (ID #%1$d: %2$s) has %3$d %4$s in total, including %5$d active %6$s.',
				'flexible-subscriptions'
			),
			$user_id,
			$deleted_user->display_name,
			$total_subs,
			_n( 'subscription', 'subscriptions', $total_subs, 'flexible-subscriptions' ),
			$active_subs,
			_n( 'subscription', 'subscriptions', $active_subs, 'flexible-subscriptions' )
		);

		$link = $this->get_user_subscriptions_link( $deleted_user );

		return sprintf( '<div><p>%s %s</p></div>', esc_html( $text ), $link );
	}

	private function get_user_subscriptions_link( WP_User $user ): string {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$url = admin_url( 'admin.php?page=wc-orders--fsb_subscription&s=' . rawurlencode( $user->user_email ) . '&search-filter=customer_email' );
		} else {
			$url = admin_url( 'edit.php?post_type=fsb_subscription&_customer_user=' . $user->ID );
		}

		return '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check user\'s subscriptions here.', 'flexible-subscriptions' ) . '</a>';
	}
}
