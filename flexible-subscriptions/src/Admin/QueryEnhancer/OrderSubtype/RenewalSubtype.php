<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtype;

use WPDesk\FlexibleSubscriptions\Subscription\Renewal\Renewal;

final class RenewalSubtype implements SubtypeEnhancer {

	public function is_needed( string $subtype ): bool {
		return $subtype === Renewal::ORDER_TYPE_VALUE;
	}

	public function enhance( array $query, string $subtype ): array {
		$query['meta_query'][] = [
			[
				'key'     => Renewal::META_ORDER_TYPE,
				'value'   => Renewal::ORDER_TYPE_VALUE,
				'compare' => '=',
			],
		];

		return $query;
	}
}
