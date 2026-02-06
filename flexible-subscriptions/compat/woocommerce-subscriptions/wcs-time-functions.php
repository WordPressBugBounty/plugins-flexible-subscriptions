<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wcs_get_subscription_period_strings' ) ) {
	/**
	 * Return an i18n'ified associative array of all possible subscription periods.
	 *
	 * @param int (optional) An interval in the range 1-6
	 * @param string (optional) One of day, week, month or year. If empty, all subscription ranges are returned.
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v2.0
	 */
	function wcs_get_subscription_period_strings( $number = 1, $period = '' ) {

		$translated_periods = apply_filters( 'woocommerce_subscription_periods',
			array(
				// translators: placeholder is number of days. (e.g. "Bill this every day / 4 days")
				'day'   => sprintf( _nx( 'day',   '%s days',   $number, 'Subscription billing period.', 'flexible-subscriptions' ), $number ), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder,WordPress.WP.I18n.MismatchedPlaceholders
				// translators: placeholder is number of weeks. (e.g. "Bill this every week / 4 weeks")
				'week'  => sprintf( _nx( 'week',  '%s weeks',  $number, 'Subscription billing period.', 'flexible-subscriptions' ), $number ), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder,WordPress.WP.I18n.MismatchedPlaceholders
				// translators: placeholder is number of months. (e.g. "Bill this every month / 4 months")
				'month' => sprintf( _nx( 'month', '%s months', $number, 'Subscription billing period.', 'flexible-subscriptions' ), $number ), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder,WordPress.WP.I18n.MismatchedPlaceholders
				// translators: placeholder is number of years. (e.g. "Bill this every year / 4 years")
				'year'  => sprintf( _nx( 'year',  '%s years',  $number, 'Subscription billing period.', 'flexible-subscriptions' ), $number ), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder,WordPress.WP.I18n.MismatchedPlaceholders
			),
			$number
		);
		// phpcs:enable

		return ( ! empty( $period ) ) ? $translated_periods[ $period ] : $translated_periods;
	}
}
