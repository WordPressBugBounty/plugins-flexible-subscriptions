<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\QueryEnhancer\OrderSubtype;

interface SubtypeEnhancer {

	public function is_needed( string $subtype ): bool;

	public function enhance( array $query, string $subtype ): array;
}
