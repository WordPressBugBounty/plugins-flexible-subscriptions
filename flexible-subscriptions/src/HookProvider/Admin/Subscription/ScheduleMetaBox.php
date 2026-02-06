<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Clock\ClockInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class ScheduleMetaBox implements HookProvider {

	private SubscriptionFinder $finder;

	private Renderer $renderer;

	private ClockInterface $clock;

	public function __construct(
		SubscriptionFinder $finder,
		Renderer $renderer,
		ClockInterface $clock
	) {
		$this->finder   = $finder;
		$this->renderer = $renderer;
		$this->clock    = $clock;
	}

	public function hooks(): void {
		add_action( 'add_meta_boxes', $this );
	}

	public function __invoke(): void {
		\add_meta_box(
			'flexible-subscription-schedule',
			_x( 'Payment cycles', 'meta box title', 'flexible-subscriptions' ),
			[ $this, 'output_metabox' ],
			wc_get_page_screen_id( 'fsb-subscription' ),
			'side',
			'default'
		);
	}

	/**
	 * @param \WP_Post|Subscription $subscription_post
	 */
	public function output_metabox( $subscription_post ): void {
		if ( $subscription_post instanceof \WP_Post ) {
			$subscription = $this->finder->find( $subscription_post->ID );
		} else {
			$subscription = $subscription_post;
		}

		if ( ! $subscription instanceof Subscription ) {
			throw new \RuntimeException( 'Invalid subscription object.' );
		}

		$this->renderer->output_render(
			'subscription/metabox/schedule',
			[
				'subscription' => $subscription,
				'clock'        => $this->clock,
			]
		);
	}
}
