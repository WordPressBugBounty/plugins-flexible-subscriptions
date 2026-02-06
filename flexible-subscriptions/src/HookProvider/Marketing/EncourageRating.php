<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Marketing;

use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class EncourageRating implements HookProvider {

	public function hooks(): void {
		add_filter( 'admin_footer_text', $this );
	}

	/**
	 * @param string $footer_text
	 *
	 * @return string
	 */
	public function __invoke( $footer_text ) {
		if ( $this->should_show_rate_notice() ) {
				$footer_text = sprintf(
					/* translators: 1: WooCommerce 2:: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'flexible-subscriptions' ),
					'<strong>WP Desk</strong>',
					'<a href="https://wordpress.org/support/plugin/flexible-subscriptions/reviews?rate=5#new-post" target="_blank" class="wc-rating-link" aria-label="' . esc_attr__( 'five star', 'flexible-subscriptions' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
		}

		return $footer_text;
	}

	private function should_show_rate_notice(): bool {
		/** @var \WP_Screen $current_screen */
		global $current_screen;

		return $current_screen->id === 'flexible-subscriptions_page_wpdesk-marketing' ||
			$current_screen->post_type === 'fsb_subscription';
	}
}
