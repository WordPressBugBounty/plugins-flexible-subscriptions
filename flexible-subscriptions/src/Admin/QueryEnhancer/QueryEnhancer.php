<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer;

interface QueryEnhancer {

	/**
	 * Add or modify current {@see \WP_Query} args.
	 */
	public function enhance_query( array $query ): array;
}
