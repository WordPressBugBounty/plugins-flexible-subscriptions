<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtype;

final class FilterableSubtype implements SubtypeEnhancer {

	public function is_needed( string $subtype ): bool {
		return true;
	}

	public function enhance( array $query, string $subtype ): array {
		$filter_query = apply_filters(
			'fsub/admin/order/subtype_query_args',
			[],
			$subtype,
		);

		if ( $filter_query !== [] ) {
			$query['meta_query'][] = $filter_query;
		}

		return $query;
	}
}
