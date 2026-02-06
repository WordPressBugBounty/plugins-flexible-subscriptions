<?php


if ( ! function_exists( 'wcs_get_objects_property' ) ) {
	/**
	 * Access an object's property in a way that is compatible with CRUD and non-CRUD APIs for different versions of WooCommerce.
	 *
	 * We don't want to force the use of a custom legacy class for orders, similar to WC_Subscription_Legacy, because 3rd party
	 * code may expect the object type to be WC_Order with strict type checks.
	 *
	 * A note on dates: in WC 3.0+, dates are returned a timestamps in the site's timezone :upside_down_face:. In WC < 3.0, they were
	 * returned as MySQL strings in the site's timezone. We return them from here as MySQL strings in UTC timezone because that's how
	 * dates are used in Subscriptions in almost all cases, for sanity's sake.
	 *
	 * @param WC_Order|WC_Product|WC_Subscription $object   The object whose property we want to access.
	 * @param string                              $property The property name.
	 * @param string                              $single   Whether to return just the first piece of meta data with the given property key, or all meta data.
	 * @param mixed                               $default  (optional) The value to return if no value is found - defaults to single -> null, multiple -> array().
	 *
	 * @since  1.0.0 - Migrated from WooCommerce Subscriptions v2.2.0
	 * @deprecated 2.4.0 Use of this compatibility function is no longer required, getters should be used on the objects instead. Please note there may be differences in dates between this function and the getter.
	 * @return mixed
	 */
	function wcs_get_objects_property( $object, $property, $single = 'single', $default = null ) {
		$value = ! is_null( $default ) ? $default : ( ( 'single' === $single ) ? null : [] );

		if ( ! is_object( $object ) ) {
			return $value;
		}

		if ( ! $object instanceof \WC_Subscription ) {
			return $value;
		}

		if ( ! is_callable( [ $object, 'get_' . $property ] ) ) {
			return $value;
		}

		try {
			return $object->{'get_' . $property}();
		} catch ( \Throwable $e ) {
			return $value;
		}
	}
}
