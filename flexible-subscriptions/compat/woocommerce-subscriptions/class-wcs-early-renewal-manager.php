<?php

class WCS_Early_Renewal_Manager {

	/**
	 * A helper function to check if the early renewal feature is enabled or not.
	 *
	 * If the setting hasn't been set yet, by default it is off for existing stores and on for new stores.
	 *
	 * @since 2.3.0
	 * @return bool
	 */
	public static function is_early_renewal_enabled() {
		return false;
	}

	/**
	 * Finds if the store has enabled early renewal via a modal.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public static function is_early_renewal_via_modal_enabled() {
		return false;
	}

	/**
	 * Gets the dates which need to be updated after an early renewal is processed.
	 *
	 * @since 2.6.0
	 *
	 * @param WC_Subscription $subscription The subscription to calculate the dates for.
	 * @return array The subscription dates which need to be updated. For example array( $date_type => $mysql_form_date_string ).
	 */
	public static function get_dates_to_update( $subscription ) {
		return [];
	}
}
