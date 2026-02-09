<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\Notice;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Notice\PermanentDismissibleNotice;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Display notices in subscription view, when there are some configuration errors.
 */
final class SubscriptionErrorsNotice implements HookProvider {

	private SubscriptionFinder $finder;

	public function __construct( SubscriptionFinder $finder ) {
		$this->finder = $finder;
	}

	public function hooks(): void {
		add_action( 'admin_notices', [ $this, '__invoke' ] );
	}

	public function __invoke(): void {
		$screen = get_current_screen();
		if ( $screen->post_type !== 'fsb_subscription' || ! isset( $_GET['action'], $_GET['id'] ) || 'edit' !== $_GET['action'] ) {
			return;
		}

		$subscription = $this->finder->find( absint( $_GET['id'] ) );

		if ( ! $subscription instanceof Subscription ) {
			return;
		}

		$hpos_sync_enabled = wc_get_container()->get( DataSynchronizer::class )->data_sync_is_enabled();

		if ( $hpos_sync_enabled ) {
			new PermanentDismissibleNotice(
				esc_html__( 'There is a known issue with manually editing subscriptions while using HPOS sync mode. For the best experience, we recommend using either High-performance order storage or WordPress posts storage mode.', 'flexible-subscriptions' ),
				'fsub_hpos_sync_enabled_warning',
				'warning'
			);
		}

		if ( ! $subscription->get_start_date() instanceof \DateTimeInterface ) {
			new Notice(
				esc_html__( 'Subscription misconfiguration: The start date for this subscription is not set. This will likely cause errors with subscription functionality. Please edit the subscription and set the start date to resolve this issue.', 'flexible-subscriptions' ),
				'error'
			);
		}

		if ( $subscription->is_finalized() ) {
			return;
		}

		$billing_frequency = $subscription->get_billing_frequency();

		if ( $billing_frequency->isEmpty() ) {
			new Notice(
				esc_html__( 'Subscription misconfiguration: The billing frequency for this subscription is not set. This will likely cause errors with subscription functionality. Please edit the subscription and set the billing frequency to resolve this issue.', 'flexible-subscriptions' ),
				'error'
			);
		}
	}
}
