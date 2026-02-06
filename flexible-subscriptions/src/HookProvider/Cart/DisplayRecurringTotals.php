<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Cart;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Formatting\Price\ProductFullPrice;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProductWrapper;
use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;

class DisplayRecurringTotals implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private Renderer $renderer;

	public function __construct(
		SubscriptionCandidatesList $candidates,
		Renderer $renderer
	) {
		$this->candidates = $candidates;
		$this->renderer   = $renderer;
	}

	public function hooks(): void {
		add_action( 'woocommerce_cart_totals_after_order_total', $this );
		add_action( 'woocommerce_review_order_after_order_total', $this );

		add_filter( 'woocommerce_cart_product_subtotal', [ $this, 'format_cart_totals' ], 11, 4 );
	}

	public function __invoke(): void {
		if ( count( $this->candidates ) === 0 ) {
			return;
		}

		$this->renderer->output_render(
			'checkout/recurring-totals',
			[ 'candidates' => $this->candidates ]
		);
	}

	public function format_cart_totals( $product_subtotal, $product, $quantity, $cart ) {
		if ( ! $product instanceof SubscriptionProduct ) {
			return $product_subtotal;
		}

		return (string) new ProductFullPrice(
			new SubscriptionProductWrapper( $product )
		);
	}
}
