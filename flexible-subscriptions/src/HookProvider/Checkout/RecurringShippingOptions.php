<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Checkout;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class RecurringShippingOptions implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private Renderer $renderer;

	public function __construct( SubscriptionCandidatesList $candidates, Renderer $renderer ) {
		$this->candidates = $candidates;
		$this->renderer   = $renderer;
	}

	public function hooks(): void {
		add_action( 'woocommerce_review_order_after_shipping', $this );
	}

	public function __invoke(): void {
		if ( count( $this->candidates ) === 0 ) {
			return;
		}

		foreach ( $this->candidates as $candidate ) {
			$this->renderer->output_render(
				'checkout/recurring-shipping',
				[
					'candidate' => $candidate,
				]
			);
		}
	}
}
