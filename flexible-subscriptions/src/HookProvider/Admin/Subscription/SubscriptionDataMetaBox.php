<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin\Subscription;

use WPDesk\FlexibleSubscriptions\PaymentMethodSeeker;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionFinder;
use WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class SubscriptionDataMetaBox extends \WC_Meta_Box_Order_Data implements HookProvider {

	/** @var SubscriptionFinder */
	private $finder;

	/** @var PaymentMethodSeeker */
	private $payment_method_seeker;

	/** @var Renderer */
	private $renderer;

	private SubscriptionLifecycleManager $lifecycle;

	public function __construct(
		SubscriptionFinder $finder,
		PaymentMethodSeeker $payment_method_seeker,
		SubscriptionLifecycleManager $lifecycle,
		Renderer $renderer
	) {
		$this->finder                = $finder;
		$this->payment_method_seeker = $payment_method_seeker;
		$this->lifecycle             = $lifecycle;
		$this->renderer              = $renderer;
	}

	public function hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 25 );
		add_action( 'add_meta_boxes', [ $this, 'remove_meta_boxes' ], 35 );
	}

	public function add_meta_boxes(): void {
		$screen = wc_get_page_screen_id( 'fsb_subscription' );

		\add_meta_box(
			'flexible-subscription-data',
			_x( 'Subscription data', 'meta box title', 'flexible-subscriptions' ),
			[ $this, 'output_metabox' ],
			$screen,
			'normal',
			'high'
		);
		\remove_meta_box( 'woocommerce-order-data', $screen, 'normal' );
	}

	public function remove_meta_boxes(): void {
		\remove_meta_box( 'woocommerce-order-data', wc_get_page_screen_id( 'fsb_subscription' ), 'normal' );
	}

	/**
	 * @param \WP_Post|Subscription $subscription_post
	 */
	public function output_metabox( $subscription_post ): void {
		if ( $subscription_post instanceof \WP_Post ) {
			$subscription = $this->finder->find( $subscription_post->ID );
		} elseif ( $subscription_post instanceof Subscription ) {
			$subscription = $subscription_post;
		}

		if ( ! $subscription instanceof Subscription ) {
			throw new \RuntimeException( 'Invalid subscription object.' );
		}

		self::init_address_fields();

		/**
		 * Filters the payment meta fields for a Flexible Subscriptions subscription.
		 *
		 * @since 1.6.0
		 *
		 * @param array<string,array<string,mixed>> $payment_meta_fields
		 * @param Subscription $subscription        The subscription object.
		 */
		$payment_meta_fields = apply_filters( 'fsub/subscription/payment_meta', [], $subscription );

		$this->renderer->output_render(
			'subscription/metabox/details',
			[
				'subscription'        => $subscription,
				'lifecycle'           => $this->lifecycle,
				'billing_fields'      => self::$billing_fields,
				'shipping_fields'     => self::$shipping_fields,
				'payment_methods'     => $this->find_payment_methods( $subscription ),
				'payment_meta_fields' => (array) $payment_meta_fields,
			]
		);
	}

	/** @return array<string, string> */
	private function find_payment_methods( Subscription $subscription ): array {
		$methods        = $this->payment_method_seeker->get_valid_payment_methods( $subscription );
		$payment_method = $subscription->get_payment_method();
		if ( ! $subscription->is_manual() && ! isset( $methods[ $payment_method ] ) ) {
			$payment_gateway = $this->payment_method_seeker->get_payment_gateway( $payment_method );

			if ( $payment_gateway instanceof \WC_Payment_Gateway ) {
				$methods[ $payment_method ] = $payment_gateway->title;
			}
		}
		return $methods;
	}
}
