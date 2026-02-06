<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Admin;

use WPDesk\FlexibleSubscriptions\Product\SubscriptionProduct;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

final class SubscriptionEditedMessages implements HookProvider {
	public function hooks(): void {
		add_filter( 'post_updated_messages', $this );
	}

	/**
	 * @param array<string, string[]> $messages
	 *
	 * @return array<string, string[]>
	 */
	public function __invoke( $messages ) {
		global $post, $post_ID;

		$messages['fsb_subscription'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Subscription updated.', 'flexible-subscriptions' ),
			2  => __( 'Custom field updated.', 'flexible-subscriptions' ),
			3  => __( 'Custom field deleted.', 'flexible-subscriptions' ),
			4  => __( 'Subscription updated.', 'flexible-subscriptions' ),
			// translators: placeholder is previous post title.
			5  => isset( $_GET['revision'] ) ? sprintf( _x( 'Subscription restored to revision from %s', 'used in post updated messages', 'flexible-subscriptions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Subscription updated.', 'flexible-subscriptions' ),
			7  => __( 'Subscription saved.', 'flexible-subscriptions' ),
			8  => __( 'Subscription submitted.', 'flexible-subscriptions' ),
			10 => __( 'Subscription draft updated.', 'flexible-subscriptions' ),
		];

		return $messages;
	}
}
