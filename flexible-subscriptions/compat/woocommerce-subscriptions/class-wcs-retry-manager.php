<?php
/**
 * Manage the process of retrying a failed renewal payment that previously failed.
 */

class WCS_Retry_Manager {

	/**
	 * Access the object used to interface with the store.
	 *
	 * @return WCS_Retry_Store
	 */
	public static function store() {
		return new \WCS_Retry_Store();
	}

	public static function is_retry_enabled(): bool {
		return false;
	}
}
