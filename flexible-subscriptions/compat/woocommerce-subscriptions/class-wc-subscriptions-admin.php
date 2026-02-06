<?php

if ( class_exists( 'WC_Subscriptions_Admin' ) ) {
	return;
}

class WC_Subscriptions_Admin {

	/**
	 * The prefix for subscription settings
	 */
	public static $option_prefix = 'flexible_subscriptions';
}
