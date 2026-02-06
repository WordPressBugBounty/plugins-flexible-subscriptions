<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Payment\PaymentRequestFinder;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class PaymentRequestsMetaBox implements HookProvider {

	/** @var Renderer */
	private $renderer;

	/** @var SubscriptionFinder */
	private $finder;

	/** @var PaymentRequestFinder */
	private $request_finder;


	public function __construct(
		SubscriptionFinder $finder,
		PaymentRequestFinder $request_finder,
		Renderer $renderer
	) {
		$this->renderer       = $renderer;
		$this->finder         = $finder;
		$this->request_finder = $request_finder;
	}

	public function hooks(): void {
		add_action( 'add_meta_boxes', $this );
	}

	public function __invoke(): void {
		\add_meta_box(
			'flexible-subscription-renewals',
			__( 'Subscription history', 'flexible-subscriptions' ),
			[ $this, 'output_metabox' ],
			wc_get_page_screen_id( 'fsb_subscription' ),
			'normal',
			'low'
		);
	}

	/**
	 * @param \WP_Post|Subscription $subscription_post
	 */
	public function output_metabox( $subscription_post ): void {
		if ( $subscription_post instanceof \WP_Post ) {
			$subscription = $this->finder->find( $subscription_post->ID );
		} elseif ( $subscription_post instanceof Subscription ) {
			$subscription = $subscription_post;
		} else {
			throw new \RuntimeException( 'Invalid subscription object.' );
		}

		if ( ! $subscription instanceof Subscription ) {
			throw new \RuntimeException( 'Invalid subscription object.' );
		}

		$this->renderer->output_render(
			'subscription/metabox/related-orders',
			[
				'payment_requests' => $this->request_finder->find_for_subscription( $subscription ),
			]
		);
	}
}
