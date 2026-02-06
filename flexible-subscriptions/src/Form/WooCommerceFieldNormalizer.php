<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

/**
 * Normalizes our wp-forms fields to standard WooCommerce array field format.
 */
class WooCommerceFieldNormalizer {

	public function normalize( Field $field ): array {
		if ( is_iterable( $field ) ) {
			return array_map( [ $this, 'normalize' ], iterator_to_array( $field ) );
		}

		return [
			'title'    => $field->get_label(),
			'desc'     => $field->get_description(),
			'id'       => $field->get_name(),
			'type'     => $field->get_type(),
			'default'  => $field->get_default_value(),
			'desc_tip' => $field->has_description(),
		];
	}
}
